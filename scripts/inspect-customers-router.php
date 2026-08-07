<?php

/**
 * List the customers on record and the users currently provisioned on the
 * router, so the reset covers the router too and does not wipe anyone real.
 *
 * Run: php scripts/inspect-customers-router.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo '=== Wateja waliopo ==='.PHP_EOL;

foreach (DB::table('customers')->orderBy('id')->get() as $customer) {
    echo sprintf(
        "  #%-3s %-22s %-16s %s\n",
        $customer->id,
        $customer->name ?? '-',
        $customer->phone ?? '-',
        $customer->created_at ?? '-'
    );
}

echo PHP_EOL.'=== Vocha zilizopo ==='.PHP_EOL;

foreach (DB::table('vouchers')->orderBy('id')->get() as $voucher) {
    echo sprintf(
        "  #%-3s %-14s %-9s chanzo=%-8s router_user=%s\n",
        $voucher->id,
        $voucher->code,
        $voucher->status,
        $voucher->source ?? '-',
        $voucher->mikrotik_username ?? '-'
    );
}

echo PHP_EOL.'=== Router: watumiaji wa hotspot ==='.PHP_EOL;

try {
    $settings = App\Models\RouterSetting::resolve();

    $client = new RouterOS\Client([
        'host' => $settings->router_ip,
        'port' => $settings->router_port ?: 8728,
        'user' => $settings->router_username,
        'pass' => $settings->router_password,
        'timeout' => 10,
        'attempts' => 2,
    ]);

    $users = $client->query('/ip/hotspot/user/print')->read();

    foreach ($users as $user) {
        echo sprintf(
            "  %-24s profile=%-20s hali=%s\n",
            $user['name'] ?? '-',
            $user['profile'] ?? '-',
            ($user['disabled'] ?? 'false') === 'true' ? 'imezimwa' : 'hai'
        );
    }

    echo '  Jumla: '.count($users).PHP_EOL;

    $active = $client->query('/ip/hotspot/active/print')->read();

    echo PHP_EOL.'=== Router: vipindi vinavyoendelea sasa ==='.PHP_EOL;

    foreach ($active as $session) {
        echo sprintf(
            "  %-24s %-16s %s\n",
            $session['user'] ?? '-',
            $session['address'] ?? '-',
            $session['mac-address'] ?? '-'
        );
    }

    echo '  Jumla: '.count($active).PHP_EOL;

    $hosts = $client->query('/ip/hotspot/host/print')->read();

    echo PHP_EOL.'=== Router: vifaa vilivyoonekana (hosts) ==='.PHP_EOL;
    echo '  Jumla: '.count($hosts).PHP_EOL;
} catch (Throwable $exception) {
    echo '  Router haikufikika: '.trim($exception->getMessage()).PHP_EOL;
}

echo PHP_EOL;
