<?php

/**
 * Print the live MikroTik configuration that the billing system depends on.
 * Run: php scripts/inspect-router-config.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\RouterSetting::resolve();

if (! $settings->router_ip || ! $settings->router_username) {
    echo 'Weka taarifa za router kwenye ukurasa /router kwanza.'.PHP_EOL;
    exit(1);
}

$client = new RouterOS\Client([
    'host' => $settings->router_ip,
    'port' => $settings->router_port ?: 8728,
    'user' => $settings->router_username,
    'pass' => $settings->router_password,
    'timeout' => 5,
    'attempts' => 2,
]);

/** @var array<string, array<int, string>> $sections */
$sections = [
    '/interface/print' => ['name', 'type', 'running', 'disabled'],
    '/ip/address/print' => ['address', 'interface', 'disabled'],
    '/ip/dhcp-server/print' => ['name', 'interface', 'address-pool', 'disabled'],
    '/ip/dhcp-server/network/print' => ['address', 'gateway', 'dns-server'],
    '/ip/pool/print' => ['name', 'ranges'],
    '/ip/hotspot/print' => ['name', 'interface', 'address-pool', 'profile', 'disabled'],
    '/ip/hotspot/profile/print' => ['name', 'hotspot-address', 'dns-name', 'login-by', 'html-directory'],
    '/ip/hotspot/user/profile/print' => ['name', 'rate-limit', 'shared-users'],
    '/ip/hotspot/walled-garden/print' => ['dst-host', 'dst-port', 'action', 'comment', 'disabled'],
    '/ip/hotspot/walled-garden/ip/print' => ['dst-address', 'dst-port', 'protocol', 'action', 'comment', 'disabled'],
    '/ip/firewall/nat/print' => ['chain', 'action', 'src-address', 'dst-address', 'out-interface', 'comment', 'disabled'],
    '/ip/service/print' => ['name', 'port', 'address', 'disabled'],
    '/ip/dns/print' => ['servers', 'allow-remote-requests'],
    '/ip/dhcp-client/print' => ['interface', 'status', 'address', 'disabled'],
    '/ip/route/print' => ['dst-address', 'gateway', 'active', 'distance'],
    '/interface/bridge/port/print' => ['bridge', 'interface', 'disabled'],
    '/interface/wireless/print' => ['name', 'ssid', 'mode', 'disabled'],
    '/ip/dhcp-server/lease/print' => ['address', 'mac-address', 'host-name', 'status'],
];

foreach ($sections as $command => $columns) {
    echo PHP_EOL.'=== '.$command.' ==='.PHP_EOL;

    try {
        $rows = $client->query($command)->read();
    } catch (Throwable $exception) {
        echo 'ERROR: '.$exception->getMessage().PHP_EOL;

        continue;
    }

    if ($rows === []) {
        echo '(hakuna)'.PHP_EOL;

        continue;
    }

    foreach ($rows as $row) {
        $parts = [];

        foreach ($columns as $column) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $parts[] = $column.'='.$row[$column];
        }

        echo ($parts === [] ? json_encode($row) : implode('  ', $parts)).PHP_EOL;
    }
}

echo PHP_EOL;
