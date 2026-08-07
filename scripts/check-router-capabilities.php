<?php

/**
 * Deployment options depend on what the router itself can do: the RouterOS
 * version decides whether WireGuard (for a cloud server to reach the API) or
 * containers are available, and the free storage decides what can be hosted
 * on the device.
 *
 * Run: php scripts/check-router-capabilities.php
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
    'timeout' => 25,
    'attempts' => 2,
]);

echo '=== System resource ==='.PHP_EOL;

foreach ($client->query('/system/resource/print')->read() as $row) {
    foreach (['board-name', 'version', 'architecture-name', 'cpu', 'total-memory', 'free-memory', 'total-hdd-space', 'free-hdd-space', 'uptime'] as $key) {
        if (isset($row[$key])) {
            echo sprintf("  %-20s %s\n", $key, $row[$key]);
        }
    }
}

echo PHP_EOL.'=== Packages zilizopo ==='.PHP_EOL;

foreach ($client->query('/system/package/print')->read() as $row) {
    echo sprintf(
        "  %-16s version=%-12s disabled=%s\n",
        $row['name'] ?? '-',
        $row['version'] ?? '-',
        $row['disabled'] ?? '-'
    );
}

echo PHP_EOL.'=== WireGuard inapatikana? ==='.PHP_EOL;

try {
    $client->query('/interface/wireguard/print')->read();
    echo '  NDIYO — router inaweza kuunganishwa na seva ya wingu kwa VPN.'.PHP_EOL;
} catch (Throwable $exception) {
    echo '  HAPANA — itabidi tutumie L2TP/IPsec au OpenVPN badala yake.'.PHP_EOL;
}

echo PHP_EOL.'=== Container inapatikana? ==='.PHP_EOL;

try {
    $client->query('/container/print')->read();
    echo '  NDIYO.'.PHP_EOL;
} catch (Throwable $exception) {
    echo '  HAPANA.'.PHP_EOL;
}

echo PHP_EOL;
