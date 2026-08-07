<?php

/**
 * Prove the calendar deadline end to end: redeeming a voucher must plant a
 * router-side timer, and a deadline that passed while billing was down must
 * be closed out when billing returns.
 *
 * Creates its own throwaway voucher and removes every trace afterwards.
 *
 * Run: php scripts/test-voucher-expiry.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admin;
use App\Models\Package;
use App\Models\Voucher;
use App\Services\MikrotikRouterService;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Artisan;

$voucherService = app(VoucherService::class);
$routerService = app(MikrotikRouterService::class);

$package = Package::query()->where('is_active', true)->where('duration_days', '>', 0)->orderBy('price')->first();
$admin = Admin::query()->first();

if (! $package || ! $admin) {
    echo 'Hakuna kifurushi hai au admin.'.PHP_EOL;

    exit(1);
}

$results = [];

echo '=== Kifurushi cha jaribio ==='.PHP_EOL;
echo '  '.$package->name.'  siku='.$package->duration_days.'  profile='.$package->profile_name.PHP_EOL;

echo PHP_EOL.'=== 1. Kutengeneza vocha ==='.PHP_EOL;

$voucher = $voucherService->generateSingle($package->id, (int) $admin->id);

echo '  msimbo: '.$voucher->code.PHP_EOL;

$results['imesajiliwa routerni'] = $voucher->router_synced_at !== null;

echo PHP_EOL.'=== 2. Kabla ya kutumika hakuna kipima-muda ==='.PHP_EOL;

$before = $routerService->getUserExpiries();

$results['haina kipima-muda kabla ya kutumika'] = ! array_key_exists($voucher->code, $before);

echo '  vipima-muda vilivyopo: '.count($before).PHP_EOL;

echo PHP_EOL.'=== 3. Mteja anaitumia ==='.PHP_EOL;

$voucher = $voucherService->markAsUsed($voucher);

echo '  imeanza : '.$voucher->activated_at.PHP_EOL;
echo '  itaisha : '.$voucher->expires_at.PHP_EOL;

$after = $routerService->getUserExpiries();
$timer = $after[$voucher->code] ?? null;

$results['kipima-muda kimewekwa'] = $timer !== null;

/** RouterOS reformats intervals freely, e.g. `1d 00:00:00` becomes `1d`. */
$intervalSeconds = static function (string $interval): int {
    $seconds = 0;

    if (preg_match('/(\d+):(\d+):(\d+)/', $interval, $clock)) {
        $seconds += ((int) $clock[1] * 3600) + ((int) $clock[2] * 60) + (int) $clock[3];
        $interval = str_replace($clock[0], '', $interval);
    }

    preg_match_all('/(\d+)([wdhms])/', $interval, $parts, PREG_SET_ORDER);

    foreach ($parts as $part) {
        $seconds += (int) $part[1] * match ($part[2]) {
            'w' => 604800,
            'd' => 86400,
            'h' => 3600,
            'm' => 60,
            default => 1,
        };
    }

    return $seconds;
};

if ($timer) {
    echo '  interval: '.($timer['interval'] ?? '-').PHP_EOL;
    echo '  next-run: '.($timer['next-run'] ?? '-').PHP_EOL;
    echo '  on-event: '.($timer['on-event'] ?? '-').PHP_EOL;

    $expected = (int) $package->duration_days * 86400;
    $actual = $intervalSeconds((string) ($timer['interval'] ?? ''));

    echo '  sekunde : '.$actual.' (zinazotarajiwa '.$expected.')'.PHP_EOL;

    $results['muda unalingana na kifurushi'] = abs($actual - $expected) <= 5;
}

echo PHP_EOL.'=== 4. Sync haiongezi kipima-muda cha pili ==='.PHP_EOL;

Artisan::call('vouchers:sync-router');

$repeat = $routerService->getUserExpiries();
$repeatTimer = $repeat[$voucher->code] ?? null;

$results['sync hairudufu kipima-muda'] = $repeatTimer !== null
    && ($repeatTimer['next-run'] ?? '') === ($timer['next-run'] ?? '');

echo '  next-run baada ya sync: '.($repeatTimer['next-run'] ?? '-').PHP_EOL;
echo '  vocha zote zenye muda   : '.count($repeat).PHP_EOL;

echo PHP_EOL.'=== 5. Muda ukiisha mfumo ukiwa umezimwa ==='.PHP_EOL;

$voucher->update(['expires_at' => now()->subMinutes(5)]);

echo '  tarehe imerudishwa nyuma dakika 5'.PHP_EOL;

Artisan::call('vouchers:sync-router');

echo trim(Artisan::output()).PHP_EOL;

$voucher->refresh();

$results['imewekwa expired kwenye database'] = $voucher->status === Voucher::STATUS_EXPIRED;
$results['akaunti imeondolewa routerni'] = $routerService->getHotspotUser($voucher->code) === null;
$results['kipima-muda kimeondolewa'] = ! array_key_exists($voucher->code, $routerService->getUserExpiries());

echo PHP_EOL.'=== Matokeo ==='.PHP_EOL;

$allPassed = true;

foreach ($results as $label => $passed) {
    echo '  '.($passed ? 'SAWA  ' : 'HAPANA').'  '.$label.PHP_EOL;
    $allPassed = $allPassed && $passed;
}

echo PHP_EOL.'=== Kusafisha ==='.PHP_EOL;

$routerService->removeUserExpiry($voucher->code);
$routerService->deleteHotspotUser($voucher->code);
$voucher->delete();

echo '  vocha ya jaribio imeondolewa'.PHP_EOL;

echo PHP_EOL.($allPassed
    ? 'Muda wa vocha sasa unahesabiwa kwa siku za kalenda.'
    : 'Kuna hatua iliyoshindwa — angalia hapo juu.').PHP_EOL.PHP_EOL;
