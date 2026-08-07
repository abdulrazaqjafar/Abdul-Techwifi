<?php

/**
 * Decide whether this board can take a RouterOS upgrade, and what is already
 * available on the device, before we download or reboot anything.
 *
 * Run: php scripts/check-upgrade-readiness.php
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

echo '=== Board na toleo ==='.PHP_EOL;

$resource = $client->query('/system/resource/print')->read()[0] ?? [];
foreach (['board-name', 'version', 'architecture-name', 'cpu', 'total-memory', 'free-memory', 'total-hdd-space', 'free-hdd-space', 'uptime'] as $field) {
    if (array_key_exists($field, $resource)) {
        echo sprintf("  %-20s %s\n", $field, $resource[$field]);
    }
}

echo PHP_EOL.'=== Identity / routerboard ==='.PHP_EOL;

try {
    $rb = $client->query('/system/routerboard/print')->read()[0] ?? [];
    foreach (['model', 'serial-number', 'firmware-type', 'current-firmware', 'upgrade-firmware', 'factory-firmware'] as $field) {
        if (array_key_exists($field, $rb)) {
            echo sprintf("  %-20s %s\n", $field, $rb[$field]);
        }
    }
} catch (Throwable $e) {
    echo '  '.$e->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== Packages zilizosakinishwa ==='.PHP_EOL;

foreach ($client->query('/system/package/print')->read() as $pkg) {
    echo sprintf(
        "  %-18s %-12s %s\n",
        $pkg['name'] ?? '-',
        $pkg['version'] ?? '-',
        (($pkg['disabled'] ?? 'false') === 'true') ? 'IMEZIMWA' : 'hai'
    );
}

echo PHP_EOL.'=== Package update channel ==='.PHP_EOL;

try {
    $upd = $client->query('/system/package/update/print')->read()[0] ?? [];
    foreach (['channel', 'installed-version', 'latest-version', 'status'] as $field) {
        if (array_key_exists($field, $upd)) {
            echo sprintf("  %-20s %s\n", $field, $upd[$field]);
        }
    }
} catch (Throwable $e) {
    echo '  '.$e->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== Faili za .npk zilizopo flash ==='.PHP_EOL;

$npk = 0;
foreach ($client->query('/file/print')->read() as $file) {
    $name = (string) ($file['name'] ?? '');
    if (! str_ends_with(strtolower($name), '.npk')) {
        continue;
    }
    $npk++;
    echo sprintf("  %-40s %s\n", $name, $file['size'] ?? '-');
}
if ($npk === 0) {
    echo '  (hakuna)'.PHP_EOL;
}

echo PHP_EOL.'=== Hotspot / sessions (kabla ya reboot) ==='.PHP_EOL;

try {
    echo '  hotspot users  : '.count($client->query('/ip/hotspot/user/print')->read()).PHP_EOL;
    echo '  active sessions: '.count($client->query('/ip/hotspot/active/print')->read()).PHP_EOL;
    echo '  schedulers     : '.count($client->query('/system/scheduler/print')->read()).PHP_EOL;
} catch (Throwable $e) {
    echo '  '.$e->getMessage().PHP_EOL;
}

echo PHP_EOL;
