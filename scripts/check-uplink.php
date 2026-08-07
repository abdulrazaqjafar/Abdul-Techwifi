<?php

/**
 * Ask the router directly whether it can reach the internet right now, and show
 * the counters of the rules that client traffic depends on. Used to separate an
 * unstable upstream link from a misconfiguration on our side.
 *
 * Run: php scripts/check-uplink.php
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
    'timeout' => 30,
    'attempts' => 2,
]);

echo '=== Router yenyewe inafikia intaneti? ==='.PHP_EOL;

foreach (['http://neverssl.com/', 'http://1.1.1.1/'] as $url) {
    $started = microtime(true);

    try {
        $client->query(
            (new RouterOS\Query('/tool/fetch'))
                ->equal('url', $url)
                ->equal('dst-path', 'uplink-test.txt')
                ->equal('mode', 'http')
        )->read();

        echo sprintf("  OK   %-26s (%.1fs)\n", $url, microtime(true) - $started);
    } catch (Throwable $exception) {
        echo sprintf("  FAIL %-26s %s\n", $url, trim($exception->getMessage()));
    }
}

echo PHP_EOL.'=== Route ya default ==='.PHP_EOL;

foreach ($client->query('/ip/route/print')->read() as $row) {
    if (($row['dst-address'] ?? '') !== '0.0.0.0/0') {
        continue;
    }

    echo sprintf(
        "  gateway=%-16s status=%-14s distance=%s\n",
        $row['gateway'] ?? '-',
        ($row['active'] ?? 'false') === 'true' ? 'active' : 'INACTIVE',
        $row['distance'] ?? '-'
    );
}

echo PHP_EOL.'=== DHCP client ya WAN ==='.PHP_EOL;

foreach ($client->query('/ip/dhcp-client/print')->read() as $row) {
    echo sprintf(
        "  interface=%-10s status=%-12s address=%-18s gateway=%s\n",
        $row['interface'] ?? '-',
        $row['status'] ?? '-',
        $row['address'] ?? '-',
        $row['gateway'] ?? '-'
    );
}

echo PHP_EOL.'=== Rules ambazo traffic ya wateja inategemea ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
    if (($row['action'] ?? '') !== 'masquerade') {
        continue;
    }

    echo sprintf(
        "  masquerade src=%-18s packets=%-10s bytes=%s\n",
        $row['src-address'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    echo sprintf(
        "  %-28s packets=%-10s bytes=%s\n",
        $row['comment'] ?? ($row['action'] ?? '-'),
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

echo PHP_EOL.'=== Sessions ==='.PHP_EOL;

$active = $client->query('/ip/hotspot/active/print')->read();

if ($active === []) {
    echo '  (hakuna)'.PHP_EOL;
}

foreach ($active as $row) {
    echo sprintf(
        "  user=%-22s address=%-15s uptime=%-10s bytes-in=%-12s bytes-out=%s\n",
        $row['user'] ?? '-',
        $row['address'] ?? '-',
        $row['uptime'] ?? '-',
        $row['bytes-in'] ?? '-',
        $row['bytes-out'] ?? '-'
    );
}

echo PHP_EOL;
