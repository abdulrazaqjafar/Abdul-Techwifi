<?php

/**
 * Point Laravel at the MikroTik VPN address after OpenVPN is up.
 *
 * Default: 10.8.0.2 (see deploy/openvpn/setup-server.sh)
 *
 * Run on the Oracle VM:
 *   php scripts/apply-oracle-router-ip.php
 *   php scripts/apply-oracle-router-ip.php 10.8.0.2
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$vpnIp = $argv[1] ?? '10.8.0.2';

if (! filter_var($vpnIp, FILTER_VALIDATE_IP)) {
    echo 'IP si sahihi: '.$vpnIp.PHP_EOL;

    exit(1);
}

$settings = App\Models\RouterSetting::resolve();
$previous = $settings->router_ip;

$settings->update([
    'router_ip' => $vpnIp,
    'is_connected' => false,
]);

echo 'router_ip: '.$previous.' → '.$vpnIp.PHP_EOL;

try {
    $result = app(App\Services\MikrotikRouterService::class)->testConnection();
    echo ($result['ok'] ? 'SAWA' : 'HAPANA').': '.$result['message'].PHP_EOL;

    if (! $result['ok']) {
        echo PHP_EOL.'Hakikisha:'.PHP_EOL;
        echo '  - OpenVPN client kwenye MikroTik inaonekana running'.PHP_EOL;
        echo '  - /ip address print inaonyesha '.$vpnIp.PHP_EOL;
        echo '  - /ip service api address inajumuisha 10.8.0.0/24'.PHP_EOL;

        exit(1);
    }
} catch (Throwable $exception) {
    echo 'Jaribio limeshindwa: '.trim($exception->getMessage()).PHP_EOL;

    exit(1);
}

echo PHP_EOL.'Laravel sasa inazungumza na router kupitia VPN.'.PHP_EOL;
