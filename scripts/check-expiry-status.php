<?php

/**
 * Line up every live voucher against the timer the router is holding for it,
 * so a mismatch shows up before a customer is cut off early or runs long.
 *
 * Run: php scripts/check-expiry-status.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Voucher;
use App\Services\MikrotikRouterService;

$routerService = app(MikrotikRouterService::class);

$expiries = $routerService->getUserExpiries();
$accounts = collect($routerService->getAllHotspotUsers())->keyBy('name');
$online = collect($routerService->getOnlineUsers())->pluck('user')->all();

echo '=== Vocha zinazotumika sasa ==='.PHP_EOL;

$vouchers = Voucher::query()
    ->with('package')
    ->where('status', Voucher::STATUS_USED)
    ->orderBy('id')
    ->get();

if ($vouchers->isEmpty()) {
    echo '  (hakuna)'.PHP_EOL;
}

foreach ($vouchers as $voucher) {
    $username = $voucher->mikrotik_username ?: $voucher->code;
    $timer = $expiries[$username] ?? null;

    echo PHP_EOL.'  '.$voucher->code.'   ('.($voucher->package->name ?? '-').')'.PHP_EOL;
    echo '    jina routerni : '.$username.PHP_EOL;
    echo '    akaunti ipo   : '.($accounts->has($username) ? 'ndiyo' : 'HAPANA').PHP_EOL;
    echo '    yupo mtandaoni: '.(in_array($username, $online, true) ? 'ndiyo' : 'hapana').PHP_EOL;
    echo '    database      : itaisha '.($voucher->expires_at?->format('d/m/Y H:i') ?? 'haijawekwa').PHP_EOL;
    echo '    router        : '.($timer
        ? 'itaisha '.($timer['next-run'] ?? '-').'  (interval '.($timer['interval'] ?? '-').')'
        : 'HAKUNA KIPIMA-MUDA').PHP_EOL;
}

echo PHP_EOL.'=== Vipima-muda vyote routerni ==='.PHP_EOL;

foreach ($expiries as $username => $timer) {
    echo sprintf(
        "  %-22s next-run=%-16s interval=%s\n",
        $username,
        $timer['next-run'] ?? '-',
        $timer['interval'] ?? '-'
    );
}

echo '  Jumla: '.count($expiries).PHP_EOL.PHP_EOL;
