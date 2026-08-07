<?php

/**
 * Drop connection-tracking entries created before source NAT was fixed.
 *
 * NAT rules only run on the first packet of a connection, so anything opened
 * while the broken rule was in place keeps flowing untranslated forever. The
 * client's DNS lookups in particular stay broken until those entries are gone.
 *
 * Run: php scripts/reset-client-connections.php
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
    'timeout' => 20,
    'attempts' => 2,
]);

$isLan = static fn (string $address): bool => str_starts_with($address, '192.168.20.');

$rows = $client->query('/ip/firewall/connection/print')->read();

$removed = 0;
$kept = 0;

foreach ($rows as $row) {
    $src = $row['src-address'] ?? '';
    $dst = $row['dst-address'] ?? '';

    if (! $isLan($src) || $isLan($dst) || ! isset($row['.id'])) {
        $kept++;

        continue;
    }

    try {
        $client->query(
            (new RouterOS\Query('/ip/firewall/connection/remove'))->equal('.id', $row['.id'])
        )->read();

        $removed++;
    } catch (Throwable $exception) {
        // Entries can expire between listing and removal; that is fine.
    }
}

echo 'Connections zilizofutwa: '.$removed.PHP_EOL;
echo 'Connections zilizoachwa (za ndani): '.$kept.PHP_EOL;

echo PHP_EOL.'=== DNS ya router inajibu wateja? ==='.PHP_EOL;

try {
    $rows = $client->query(
        (new RouterOS\Query('/ip/dns/cache/print'))
    )->read();

    echo 'Rekodi kwenye DNS cache: '.count($rows).PHP_EOL;
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== Hali ya sasa ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
    if (($row['action'] ?? '') !== 'masquerade') {
        continue;
    }

    echo sprintf(
        "masquerade src=%-18s packets=%-10s bytes=%s\n",
        $row['src-address'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

foreach ($client->query('/ip/hotspot/active/print')->read() as $row) {
    echo sprintf(
        "session user=%-22s address=%-15s bytes-in=%-12s bytes-out=%s\n",
        $row['user'] ?? '-',
        $row['address'] ?? '-',
        $row['bytes-in'] ?? '-',
        $row['bytes-out'] ?? '-'
    );
}

echo PHP_EOL.'Sasa zima WiFi kwenye simu na uiwashe tena.'.PHP_EOL;
