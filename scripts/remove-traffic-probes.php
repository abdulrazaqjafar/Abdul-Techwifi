<?php

/**
 * Remove the temporary mangle counters added by add-traffic-probes.php.
 * Run: php scripts/remove-traffic-probes.php
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
    'timeout' => 15,
    'attempts' => 2,
]);

$removed = 0;

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    if (! str_starts_with($row['comment'] ?? '', 'probe-') || ! isset($row['.id'])) {
        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/firewall/mangle/remove'))->equal('.id', $row['.id'])
    )->read();

    echo 'imeondolewa: '.$row['comment'].PHP_EOL;
    $removed++;
}

echo ($removed === 0 ? '(hakuna kipimo kilichobaki)' : 'Vipimo '.$removed.' vimeondolewa.').PHP_EOL;

echo PHP_EOL.'=== Hali ya mwisho ==='.PHP_EOL;

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

echo PHP_EOL;
