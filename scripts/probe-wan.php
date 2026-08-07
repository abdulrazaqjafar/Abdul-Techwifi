<?php

/**
 * Find out which subnet the upstream (internet) network uses, by asking for a
 * DHCP lease on ether1 without touching the routing table.
 * Run: php scripts/probe-wan.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\RouterSetting::resolve();

$client = new RouterOS\Client([
    'host' => $settings->router_ip,
    'port' => $settings->router_port ?: 8728,
    'user' => $settings->router_username,
    'pass' => $settings->router_password,
    'timeout' => 5,
    'attempts' => 2,
]);

$existing = $client->query(
    (new RouterOS\Query('/ip/dhcp-client/print'))->where('interface', 'ether1')
)->read();

if ($existing === []) {
    echo 'Inaomba DHCP lease kwenye ether1 (bila kubadilisha routing)...'.PHP_EOL;

    $client->query(
        (new RouterOS\Query('/ip/dhcp-client/add'))
            ->equal('interface', 'ether1')
            ->equal('add-default-route', 'no')
            ->equal('use-peer-dns', 'no')
            ->equal('disabled', 'no')
    )->read();

    sleep(10);
}

echo PHP_EOL.'=== /ip/dhcp-client ==='.PHP_EOL;

foreach ($client->query('/ip/dhcp-client/print')->read() as $row) {
    echo sprintf(
        "interface=%-8s status=%-12s address=%s  gateway=%s\n",
        $row['interface'] ?? '-',
        $row['status'] ?? '-',
        $row['address'] ?? '-',
        $row['gateway'] ?? '-'
    );
}

echo PHP_EOL.'=== /ip/address ==='.PHP_EOL;

foreach ($client->query('/ip/address/print')->read() as $row) {
    echo sprintf(
        "%-19s interface=%-12s dynamic=%s\n",
        $row['address'] ?? '-',
        $row['interface'] ?? '-',
        $row['dynamic'] ?? '-'
    );
}

echo PHP_EOL;
