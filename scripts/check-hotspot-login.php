<?php

/**
 * The API reports a successful hotspot login but no session appears. Ask the
 * router why: the host table, the voucher user's remaining limits, and the
 * hotspot log entries.
 *
 * Run: php scripts/check-hotspot-login.php
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

echo '=== Hotspot hosts ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/host/print')->read() as $row) {
    echo sprintf(
        "  mac=%-19s address=%-15s to-address=%-15s authorized=%-6s bypassed=%s\n",
        $row['mac-address'] ?? '-',
        $row['address'] ?? '-',
        $row['to-address'] ?? '-',
        $row['authorized'] ?? '-',
        $row['bypassed'] ?? '-'
    );
}

echo PHP_EOL.'=== Mtumiaji wa vocha (kwa ukamilifu) ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/user/print')->read() as $row) {
    if (! str_starts_with($row['name'] ?? '', 'voucher_')) {
        continue;
    }

    echo json_encode($row, JSON_PRETTY_PRINT).PHP_EOL;
}

echo PHP_EOL.'=== Profile za hotspot user ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $row) {
    echo sprintf(
        "  name=%-18s shared-users=%-4s rate-limit=%-14s session-timeout=%s\n",
        $row['name'] ?? '-',
        $row['shared-users'] ?? '-',
        $row['rate-limit'] ?? '-',
        $row['session-timeout'] ?? '-'
    );
}

echo PHP_EOL.'=== Log ya hotspot (mistari ya mwisho) ==='.PHP_EOL;

$logs = $client->query('/log/print')->read();

$relevant = array_filter($logs, static function ($row): bool {
    $topics = $row['topics'] ?? '';
    $message = strtolower($row['message'] ?? '');

    return str_contains($topics, 'hotspot')
        || str_contains($message, 'login')
        || str_contains($message, 'voucher');
});

foreach (array_slice(array_values($relevant), -25) as $row) {
    echo sprintf(
        "  %-10s %-22s %s\n",
        $row['time'] ?? '-',
        $row['topics'] ?? '-',
        $row['message'] ?? '-'
    );
}

if ($relevant === []) {
    echo '  (hakuna kumbukumbu ya hotspot)'.PHP_EOL;
}

echo PHP_EOL;
