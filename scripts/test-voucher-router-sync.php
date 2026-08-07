<?php

/**
 * Prove that a voucher is usable without the billing server: generate one,
 * then read it back off the router and check the router alone has everything
 * it needs to let the customer in.
 *
 * The voucher created here is real and can be sold or used for a phone test.
 *
 * Run: php scripts/test-voucher-router-sync.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admin;
use App\Models\Package;
use App\Services\VoucherService;

$package = Package::query()->where('is_active', true)->orderBy('price')->first();
$admin = Admin::query()->first();

if (! $package || ! $admin) {
    echo 'Hakuna kifurushi hai au admin.'.PHP_EOL;

    exit(1);
}

echo '=== Kutengeneza vocha ==='.PHP_EOL;
echo '  Kifurushi: '.$package->name.' (profile '.$package->profile_name.')'.PHP_EOL;

$voucher = app(VoucherService::class)->generateSingle($package->id, (int) $admin->id);

echo '  Msimbo   : '.$voucher->code.PHP_EOL;
echo '  Router   : '.($voucher->router_synced_at ? 'imesajiliwa '.$voucher->router_synced_at : 'HAIKUSAJILIWA').PHP_EOL;

echo PHP_EOL.'=== Kuisoma kwenye router ==='.PHP_EOL;

$settings = App\Models\RouterSetting::resolve();

$client = new RouterOS\Client([
    'host' => $settings->router_ip,
    'port' => $settings->router_port ?: 8728,
    'user' => $settings->router_username,
    'pass' => $settings->router_password,
    'timeout' => 10,
    'attempts' => 2,
]);

$found = null;

foreach ($client->query('/ip/hotspot/user/print')->read() as $user) {
    if (($user['name'] ?? '') === $voucher->code) {
        $found = $user;

        break;
    }
}

if (! $found) {
    echo '  HAIPO kwenye router.'.PHP_EOL;

    exit(1);
}

echo '  jina         : '.($found['name'] ?? '-').PHP_EOL;
echo '  nenosiri     : '.($found['password'] ?? '-').PHP_EOL;
echo '  profile      : '.($found['profile'] ?? '-').PHP_EOL;
echo '  limit-uptime : '.($found['limit-uptime'] ?? 'hakuna').PHP_EOL;

echo PHP_EOL.'=== Ukaguzi ==='.PHP_EOL;

$checks = [
    'jina ni msimbo wenyewe' => ($found['name'] ?? '') === $voucher->code,
    'nenosiri ni msimbo wenyewe' => ($found['password'] ?? '') === $voucher->code,
    'profile inalingana na kifurushi' => ($found['profile'] ?? '') === $package->profile_name,
    'muda umewekwa' => ! empty($found['limit-uptime']),
];

$allPassed = true;

foreach ($checks as $label => $passed) {
    echo '  '.($passed ? 'SAWA ' : 'HAPANA').'  '.$label.PHP_EOL;

    $allPassed = $allPassed && $passed;
}

echo PHP_EOL;

if ($allPassed) {
    echo 'Vocha hii itafanya kazi hata mfumo ukiwa umezimwa.'.PHP_EOL;
    echo 'Jaribu kwenye simu: unganisha Abdul-Tech, kisha weka '.$voucher->code.PHP_EOL;
} else {
    echo 'Kuna kitu hakiko sawa — angalia ukaguzi hapo juu.'.PHP_EOL;
}

echo PHP_EOL;
