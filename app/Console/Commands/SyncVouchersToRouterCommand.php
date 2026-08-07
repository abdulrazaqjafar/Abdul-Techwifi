<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use App\Services\MikrotikRouterService;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Throwable;

class SyncVouchersToRouterCommand extends Command
{
    protected $signature = 'vouchers:sync-router {--prune : Also remove router users that no longer belong to a live voucher}';

    protected $description = 'Push unsynced vouchers to the router and record vouchers redeemed while billing was offline';

    public function handle(VoucherService $voucherService, MikrotikRouterService $routerService): int
    {
        $migrated = $this->migrateLegacyUsernames($routerService, $voucherService);
        $pushed = $this->pushPendingVouchers($voucherService);
        $recorded = $this->recordOfflineRedemptions($routerService, $voucherService);
        $expiries = $this->enforceExpiries($routerService, $voucherService);
        $pruned = $this->option('prune') ? $this->pruneStaleRouterUsers($routerService) : 0;

        $this->newLine();
        $this->info("Zimehamishwa kwa msimbo : {$migrated}");
        $this->info("Zimesajiliwa routerni : {$pushed}");
        $this->info("Zimetumika bila mfumo : {$recorded}");
        $this->info("Vipima-muda vimewekwa : {$expiries['scheduled']}");
        $this->info("Muda umeisha, zimefungwa : {$expiries['closed']}");

        if ($this->option('prune')) {
            $this->info("Zimeondolewa routerni : {$pruned}");
        }

        return self::SUCCESS;
    }

    /**
     * Vouchers issued before the router username became the voucher code
     * cannot be redeemed on the router's own login page, because that page
     * sends the typed code as the username.
     *
     * Only vouchers with no session running are moved, so nobody is knocked
     * offline; the rest are picked up on a later run.
     */
    protected function migrateLegacyUsernames(MikrotikRouterService $routerService, VoucherService $voucherService): int
    {
        $legacy = Voucher::query()
            ->with('package')
            ->whereIn('status', [Voucher::STATUS_UNUSED, Voucher::STATUS_USED])
            ->whereColumn('mikrotik_username', '!=', 'code')
            ->get();

        if ($legacy->isEmpty()) {
            return 0;
        }

        try {
            $active = collect($routerService->getOnlineUsers())->pluck('user')->filter()->all();
        } catch (Throwable $exception) {
            $this->warn('Router haikufikika: '.trim($exception->getMessage()));

            return 0;
        }

        $migrated = 0;

        foreach ($legacy as $voucher) {
            $oldName = (string) $voucher->mikrotik_username;

            if (in_array($oldName, $active, true)) {
                $this->line("  . {$voucher->code} inatumika sasa, itahamishwa baadaye");

                continue;
            }

            try {
                $routerService->deleteHotspotUser($oldName);
            } catch (Throwable $exception) {
                $this->warn("  ! {$voucher->code}: ".trim($exception->getMessage()));

                continue;
            }

            $voucher->update([
                'mikrotik_username' => $voucher->code,
                'router_synced_at' => null,
            ]);

            if ($voucherService->syncToRouter($voucher)) {
                $migrated++;
                $this->line("  > {$voucher->code}");
            }
        }

        return $migrated;
    }

    /**
     * Vouchers created while the router was unreachable carry no
     * `router_synced_at`, so they are retried here.
     */
    protected function pushPendingVouchers(VoucherService $voucherService): int
    {
        $pending = Voucher::query()
            ->with('package')
            ->where('status', Voucher::STATUS_UNUSED)
            ->whereNull('router_synced_at')
            ->get();

        $pushed = 0;

        foreach ($pending as $voucher) {
            if ($voucherService->syncToRouter($voucher)) {
                $pushed++;
                $this->line("  + {$voucher->code}");

                continue;
            }

            $this->warn("  ! {$voucher->code} haikusajiliwa");
        }

        return $pushed;
    }

    /**
     * A customer can redeem a voucher straight on the router while the billing
     * server is off. The router keeps the uptime, so that usage is copied back
     * into the database once the server returns.
     */
    protected function recordOfflineRedemptions(MikrotikRouterService $routerService, VoucherService $voucherService): int
    {
        try {
            $routerUsers = $routerService->getAllHotspotUsers();
            $activeSessions = $routerService->getOnlineUsers();
        } catch (Throwable $exception) {
            $this->warn('Router haikufikika: '.trim($exception->getMessage()));

            return 0;
        }

        $used = [];

        // RouterOS only folds a session's time into the account once the
        // session ends, so a customer online right now still reads 0s there.
        // The active list is what catches them while they are connected.
        foreach ($activeSessions as $session) {
            $name = (string) ($session['user'] ?? '');

            if ($name !== '') {
                $used[$name] = (string) ($session['uptime'] ?? 'hai sasa');
            }
        }

        foreach ($routerUsers as $user) {
            $name = (string) ($user['name'] ?? '');
            $uptime = (string) ($user['uptime'] ?? '0s');

            if ($name === '' || $this->uptimeIsZero($uptime)) {
                continue;
            }

            $used[$name] = $uptime;
        }

        if ($used === []) {
            return 0;
        }

        $vouchers = Voucher::query()
            ->with('package')
            ->where('status', Voucher::STATUS_UNUSED)
            ->whereIn('mikrotik_username', array_keys($used))
            ->get();

        $recorded = 0;

        foreach ($vouchers as $voucher) {
            try {
                $voucherService->markAsUsed($voucher);
                $recorded++;
                $this->line("  * {$voucher->code} ilitumika (uptime {$used[$voucher->mikrotik_username]})");
            } catch (Throwable $exception) {
                $this->warn("  ! {$voucher->code}: ".trim($exception->getMessage()));
            }
        }

        return $recorded;
    }

    /**
     * Codes that were cancelled or that ran out must not linger on the router
     * where they would still be accepted.
     *
     * A used voucher is deliberately kept: its owner may be connected right
     * now, and removing the router account would cut off a paying customer
     * mid-session.
     */
    /**
     * The router counts a voucher's life in time spent online, so the calendar
     * deadline is enforced by a per-voucher timer instead. Vouchers redeemed
     * while billing was down have no timer yet, and any whose deadline passed
     * during the outage are closed here.
     *
     * @return array{scheduled: int, closed: int}
     */
    protected function enforceExpiries(MikrotikRouterService $routerService, VoucherService $voucherService): array
    {
        $live = Voucher::query()
            ->with('package')
            ->where('status', Voucher::STATUS_USED)
            ->whereNotNull('expires_at')
            ->get();

        if ($live->isEmpty()) {
            return ['scheduled' => 0, 'closed' => 0];
        }

        try {
            $existing = $routerService->getUserExpiries();
        } catch (Throwable $exception) {
            $this->warn('Router haikufikika: '.trim($exception->getMessage()));

            return ['scheduled' => 0, 'closed' => 0];
        }

        $scheduled = 0;
        $closed = 0;

        foreach ($live as $voucher) {
            $username = $voucher->mikrotik_username ?: $voucher->code;

            if ($voucher->expires_at->isPast()) {
                try {
                    $routerService->removeUserExpiry($username);
                    $routerService->disconnectUser($username);
                    $routerService->deleteHotspotUser($username);
                    $voucherService->markAsExpired($voucher);

                    $closed++;
                    $this->line("  x {$voucher->code} muda umeisha");
                } catch (Throwable $exception) {
                    $this->warn("  ! {$voucher->code}: ".trim($exception->getMessage()));
                }

                continue;
            }

            if (array_key_exists($username, $existing)) {
                continue;
            }

            if ($voucherService->scheduleRouterExpiry($voucher)) {
                $scheduled++;
                $this->line("  @ {$voucher->code} itaisha {$voucher->expires_at->format('d/m/Y H:i')}");
            }
        }

        return ['scheduled' => $scheduled, 'closed' => $closed];
    }

    protected function pruneStaleRouterUsers(MikrotikRouterService $routerService): int
    {
        try {
            $routerUsers = $routerService->getAllHotspotUsers();
        } catch (Throwable $exception) {
            $this->warn('Router haikufikika: '.trim($exception->getMessage()));

            return 0;
        }

        $vouchers = Voucher::query()
            ->whereNotNull('mikrotik_username')
            ->get(['mikrotik_username', 'status', 'expires_at'])
            ->keyBy('mikrotik_username');

        $pruned = 0;

        foreach ($routerUsers as $user) {
            $name = (string) ($user['name'] ?? '');

            // Leave anything that is not a voucher code, such as accounts set
            // up by hand on the router.
            if ($name === '' || ! preg_match('/^[A-Z0-9]{5}-[A-Z0-9]{5}$/', $name)) {
                continue;
            }

            $voucher = $vouchers->get($name);

            $isStale = $voucher === null
                || in_array($voucher->status, [Voucher::STATUS_CANCELLED, Voucher::STATUS_EXPIRED], true)
                || ($voucher->expires_at !== null && $voucher->expires_at->isPast());

            if (! $isStale) {
                continue;
            }

            $routerService->removeUserExpiry($name);

            if ($routerService->deleteHotspotUser($name)) {
                $pruned++;
                $this->line("  - {$name}");
            }
        }

        return $pruned + $this->pruneOrphanExpiries($routerService);
    }

    /**
     * A timer normally deletes itself when it fires. Any left pointing at an
     * account that no longer exists would sit in the config forever.
     */
    protected function pruneOrphanExpiries(MikrotikRouterService $routerService): int
    {
        try {
            $expiries = $routerService->getUserExpiries();
            $accounts = collect($routerService->getAllHotspotUsers())->pluck('name')->filter()->all();
        } catch (Throwable $exception) {
            $this->warn('Router haikufikika: '.trim($exception->getMessage()));

            return 0;
        }

        $removed = 0;

        foreach (array_keys($expiries) as $username) {
            if (in_array($username, $accounts, true)) {
                continue;
            }

            if ($routerService->removeUserExpiry($username)) {
                $removed++;
                $this->line("  - kipima-muda cha {$username}");
            }
        }

        return $removed;
    }

    protected function uptimeIsZero(string $uptime): bool
    {
        return $uptime === '' || (bool) preg_match('/^0+s?$/', $uptime);
    }
}
