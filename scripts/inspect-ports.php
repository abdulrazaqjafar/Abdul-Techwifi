<?php

/**
 * Show which MikroTik port each device is plugged into, so we can tell the
 * internet cable apart from the billing PC and the access point.
 * Run: php scripts/inspect-ports.php
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

echo '=== /ip/arp (vifaa vinavyoonekana kwa IP) ==='.PHP_EOL;

foreach ($client->query('/ip/arp/print')->read() as $row) {
    echo sprintf(
        "%-16s %-19s interface=%s  dynamic=%s\n",
        $row['address'] ?? '-',
        $row['mac-address'] ?? '-',
        $row['interface'] ?? '-',
        $row['dynamic'] ?? '-'
    );
}

echo PHP_EOL.'=== /interface/bridge/host (MAC ipo port ipi) ==='.PHP_EOL;

$perPort = [];

foreach ($client->query('/interface/bridge/host/print')->read() as $row) {
    $port = $row['on-interface'] ?? '-';
    $perPort[$port] = ($perPort[$port] ?? 0) + 1;

    if (($row['local'] ?? 'false') === 'true') {
        continue;
    }

    echo sprintf(
        "%-19s on-interface=%-12s bridge=%s\n",
        $row['mac-address'] ?? '-',
        $port,
        $row['bridge'] ?? '-'
    );
}

echo PHP_EOL.'=== Idadi ya MAC kwa kila port (port yenye nyingi = mtandao wa nje) ==='.PHP_EOL;

foreach ($perPort as $port => $count) {
    echo sprintf("%-14s %d\n", $port, $count);
}

echo PHP_EOL.'=== Link speed kwa kila ethernet port ==='.PHP_EOL;

foreach (['LAN1', 'LAN2', 'LAN3', 'LAN4', 'ether1'] as $name) {
    try {
        $rows = $client->query(
            (new RouterOS\Query('/interface/ethernet/monitor'))
                ->equal('numbers', $name)
                ->equal('once', '')
        )->read();
    } catch (Throwable $exception) {
        echo sprintf("%-8s ERROR: %s\n", $name, $exception->getMessage());

        continue;
    }

    $row = $rows[0] ?? [];

    echo sprintf(
        "%-8s status=%-12s rate=%s\n",
        $name,
        $row['status'] ?? '-',
        $row['rate'] ?? '-'
    );
}

echo PHP_EOL;
