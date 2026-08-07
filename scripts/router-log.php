<?php

/**
 * Print the most recent MikroTik log entries plus wireless radio state.
 * Run: php scripts/router-log.php
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

echo '=== /interface/wireless (running?) ==='.PHP_EOL;

foreach ($client->query('/interface/wireless/print')->read() as $row) {
    echo sprintf(
        "%-7s ssid=%-16s disabled=%-6s running=%s\n",
        $row['name'] ?? '-',
        $row['ssid'] ?? '-',
        $row['disabled'] ?? '-',
        $row['running'] ?? '-'
    );
}

echo PHP_EOL.'=== /interface/wireless/cap (CAPsMAN inasimamia radio?) ==='.PHP_EOL;

try {
    foreach ($client->query('/interface/wireless/cap/print')->read() as $row) {
        echo json_encode($row).PHP_EOL;
    }
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== /interface/wireless/monitor wlan1 ==='.PHP_EOL;

try {
    $rows = $client->query(
        (new RouterOS\Query('/interface/wireless/monitor'))
            ->equal('numbers', 'wlan1')
            ->equal('once', '')
    )->read();

    echo json_encode($rows[0] ?? []).PHP_EOL;
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== /system/routerboard ==='.PHP_EOL;

try {
    foreach ($client->query('/system/routerboard/print')->read() as $row) {
        foreach (['model', 'firmware-type', 'current-firmware'] as $field) {
            if (isset($row[$field])) {
                echo $field.': '.$row[$field].PHP_EOL;
            }
        }
    }
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== /system/license ==='.PHP_EOL;

try {
    foreach ($client->query('/system/license/print')->read() as $row) {
        echo json_encode($row).PHP_EOL;
    }
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== /system/package (wireless imewekwa?) ==='.PHP_EOL;

try {
    foreach ($client->query('/system/package/print')->read() as $row) {
        echo sprintf("%-14s disabled=%s\n", $row['name'] ?? '-', $row['disabled'] ?? '-');
    }
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== /log (mistari 40 ya mwisho) ==='.PHP_EOL;

$entries = $client->query('/log/print')->read();

foreach (array_slice($entries, -40) as $row) {
    echo sprintf(
        "%-10s %-22s %s\n",
        $row['time'] ?? '-',
        $row['topics'] ?? '-',
        $row['message'] ?? '-'
    );
}

echo PHP_EOL;
