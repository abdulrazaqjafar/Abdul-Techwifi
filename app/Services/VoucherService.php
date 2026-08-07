<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Package;
use App\Models\Voucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VoucherService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected MikrotikRouterService $routerService
    ) {}

    public function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $raw = '';
            for ($i = 0; $i < 10; $i++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $code = substr($raw, 0, 5).'-'.substr($raw, 5);
        } while (Voucher::query()->where('code', $code)->exists());

        return $code;
    }

    public function generateSingle(int $packageId, int $adminId, ?int $customerId = null): Voucher
    {
        $package = Package::query()->findOrFail($packageId);

        if (! $package->is_active) {
            throw new RuntimeException('Cannot generate voucher for an inactive package.');
        }

        $code = $this->generateCode();

        $voucher = Voucher::query()->create([
            'code' => $code,
            'package_id' => $package->id,
            'status' => Voucher::STATUS_UNUSED,
            'admin_id' => $adminId,
            'customer_id' => $customerId,
            // The router's own login page sends the typed code as both the
            // username and the password, so the code has to be the router
            // username for the voucher to work when billing is offline.
            'mikrotik_username' => $code,
        ]);

        $this->syncToRouter($voucher, isNewCode: true);

        $this->activityLogService->log(
            'voucher.generated',
            "Generated voucher {$voucher->code} for package {$package->name}."
        );

        return $voucher->load(['package', 'admin', 'customer']);
    }

    /**
     * @return Collection<int, Voucher>
     */
    public function generateBulk(int $count, int $packageId, int $adminId, ?int $customerId = null): Collection
    {
        if ($count < 1) {
            throw new RuntimeException('Bulk voucher count must be at least 1.');
        }

        $package = Package::query()->findOrFail($packageId);

        if (! $package->is_active) {
            throw new RuntimeException('Cannot generate vouchers for an inactive package.');
        }

        $batchId = (string) Str::uuid();
        $vouchers = new Collection;

        DB::transaction(function () use ($count, $package, $adminId, $customerId, $batchId, &$vouchers): void {
            for ($index = 0; $index < $count; $index++) {
                $code = $this->generateCode();

                $vouchers->push(Voucher::query()->create([
                    'code' => $code,
                    'package_id' => $package->id,
                    'status' => Voucher::STATUS_UNUSED,
                    'admin_id' => $adminId,
                    'customer_id' => $customerId,
                    'mikrotik_username' => $code,
                    'batch_id' => $batchId,
                ]));
            }
        });

        // Outside the transaction: the router can be slow or unreachable, and
        // that must not hold a database lock or lose the codes already saved.
        foreach ($vouchers as $voucher) {
            $this->syncToRouter($voucher, isNewCode: true);
        }

        $this->activityLogService->log(
            'voucher.bulk_generated',
            "Generated {$count} vouchers for package {$package->name} (batch {$batchId})."
        );

        return $vouchers->load(['package', 'admin', 'customer']);
    }

    /**
     * Write the voucher to the router so it can be redeemed even while the
     * billing server is down.
     *
     * A router that cannot be reached must not stop a cashier from selling, so
     * this reports failure instead of throwing; `router_synced_at` stays null
     * and the voucher can be pushed later.
     */
    public function syncToRouter(Voucher $voucher, bool $isNewCode = false): bool
    {
        $package = $voucher->package ?? Package::query()->find($voucher->package_id);

        if (! $package) {
            return false;
        }

        $username = $voucher->mikrotik_username ?: $voucher->code;
        $profile = $package->profile_name ?: 'default';
        $limitUptime = $package->getRouterUptimeLimit();

        try {
            if ($isNewCode) {
                $this->routerService->createHotspotUser($username, $voucher->code, $profile, $limitUptime);
            } else {
                $this->routerService->ensureHotspotUser($username, $voucher->code, $profile, $limitUptime);
            }
        } catch (Throwable $exception) {
            Log::warning('Voucher could not be written to the router', [
                'code' => $voucher->code,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        $voucher->update(['router_synced_at' => now()]);

        return true;
    }

    /**
     * Take the voucher off the router so a cancelled or deleted code cannot
     * still be redeemed at the captive portal.
     */
    /**
     * Start the router-side countdown for a voucher that has just been
     * redeemed. The router limits time spent online, not calendar days, so
     * without this a day pass survives every short visit until 24 hours of
     * actual use have accumulated.
     */
    public function scheduleRouterExpiry(Voucher $voucher): bool
    {
        if ($voucher->expires_at === null) {
            return false;
        }

        // Rounded up so a deadline computed a fraction of a second ago still
        // buys the customer the full period they paid for.
        $seconds = (int) ceil(now()->diffInSeconds($voucher->expires_at, false));

        if ($seconds < 1) {
            return false;
        }

        try {
            return $this->routerService->scheduleUserExpiry(
                $voucher->mikrotik_username ?: $voucher->code,
                $seconds
            );
        } catch (Throwable $exception) {
            Log::warning('Voucher expiry could not be scheduled on the router', [
                'code' => $voucher->code,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function removeFromRouter(Voucher $voucher): bool
    {
        try {
            $this->routerService->removeUserExpiry($voucher->mikrotik_username ?: $voucher->code);

            return $this->routerService->deleteHotspotUser($voucher->mikrotik_username ?: $voucher->code);
        } catch (Throwable $exception) {
            Log::warning('Voucher could not be removed from the router', [
                'code' => $voucher->code,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function markAsUsed(Voucher $voucher, ?string $mikrotikUsername = null): Voucher
    {
        $voucher = DB::transaction(function () use ($voucher, $mikrotikUsername): Voucher {
            $voucher = Voucher::query()->lockForUpdate()->findOrFail($voucher->id);

            if (! $voucher->isUnused()) {
                throw new RuntimeException(__('hotspot.errors.used_voucher'));
            }

            $package = $voucher->package ?? Package::query()->findOrFail($voucher->package_id);

            $expiresAt = null;
            if ($package->duration_days > 0) {
                $expiresAt = now()->addDays($package->duration_days);
            } elseif ($package->duration_hours > 0) {
                $expiresAt = now()->addHours($package->duration_hours);
            }

            $voucher->update([
                'status' => Voucher::STATUS_USED,
                'activated_at' => now(),
                'expires_at' => $expiresAt,
                'mikrotik_username' => $mikrotikUsername ?? $voucher->mikrotik_username,
            ]);

            $this->activityLogService->log(
                'voucher.used',
                "Voucher {$voucher->code} marked as used."
            );

            return $voucher->fresh(['package', 'admin', 'customer', 'sale']);
        });

        // Kept outside the transaction so a slow or unreachable router never
        // holds a lock on the voucher row.
        $this->scheduleRouterExpiry($voucher);

        return $voucher;
    }

    public function markAsExpired(Voucher $voucher): Voucher
    {
        if ($voucher->isExpired() || $voucher->isCancelled()) {
            throw new RuntimeException('Voucher is already expired or cancelled.');
        }

        $voucher->update([
            'status' => Voucher::STATUS_EXPIRED,
        ]);

        $this->activityLogService->log(
            'voucher.expired',
            "Voucher {$voucher->code} marked as expired."
        );

        return $voucher->fresh(['package', 'admin', 'customer', 'sale']);
    }

    public function exportToPdf(array|Collection $vouchers, ?string $filename = null)
    {
        $voucherCollection = $vouchers instanceof Collection
            ? $vouchers
            : Voucher::query()->whereIn('id', $vouchers)->with('package')->get();

        $settings = BusinessSetting::getSettings();

        return Pdf::loadView('pdf.vouchers', [
            'vouchers' => $voucherCollection,
            'settings' => $settings,
        ])->download($filename ?? 'vouchers-'.now()->format('Y-m-d-His').'.pdf');
    }

    /**
     * @return Collection<int, Voucher>
     */
    public function getPrintableVouchers(?string $batchId = null, ?array $voucherIds = null): Collection
    {
        $query = Voucher::query()
            ->with(['package'])
            ->where('status', Voucher::STATUS_UNUSED)
            ->orderBy('created_at');

        if ($batchId !== null) {
            $query->where('batch_id', $batchId);
        }

        if ($voucherIds !== null) {
            $query->whereIn('id', $voucherIds);
        }

        return $query->get();
    }

}
