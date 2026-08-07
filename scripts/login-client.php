<?php

/**
 * Log the test phone into the hotspot straight through the API, so internet
 * testing no longer depends on the captive portal UI or on the session
 * surviving a WiFi toggle.
 *
 * Run: php scripts/login-client.php [ip] [mac]
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

$ip = $argv[1] ?? '192.168.20.200';
$mac = strtoupper($argv[2] ?? '42:8E:F8:C8:CC:DB');

echo '=== Watumiaji wa hotspot kwenye router ==='.PHP_EOL;

$users = $client->query('/ip/hotspot/user/print')->read();

foreach ($users as $row) {
    echo sprintf(
        "  name=%-24s profile=%-16s limit-uptime=%-10s bytes-in=%-12s bytes-out=%s\n",
        $row['name'] ?? '-',
        $row['profile'] ?? '-',
        $row['limit-uptime'] ?? '-',
        $row['bytes-in'] ?? '0',
        $row['bytes-out'] ?? '0'
    );
}

echo PHP_EOL.'=== Vocha kwenye mfumo ==='.PHP_EOL;

try {
    App\Models\Voucher::query()
        ->latest('id')
        ->limit(5)
        ->get()
        ->each(function ($voucher): void {
            echo sprintf(
                "  code=%-14s status=%-10s mac=%-19s ip=%-15s device=%s\n",
                $voucher->code,
                $voucher->status,
                $voucher->activation_mac ?: '-',
                $voucher->activation_ip ?: '-',
                $voucher->device_summary ?: '-'
            );
        });
} catch (Throwable $exception) {
    echo '  (haikusomeka: '.$exception->getMessage().')'.PHP_EOL;
}

echo PHP_EOL.'=== Vifaa vilivyo kwenye WiFi sasa ==='.PHP_EOL;

$onWifi = false;

foreach ($client->query('/interface/wireless/registration-table/print')->read() as $row) {
    $onWifi = $onWifi || strtoupper($row['mac-address'] ?? '') === $mac;

    echo sprintf("  mac=%-19s uptime=%s\n", $row['mac-address'] ?? '-', $row['uptime'] ?? '-');
}

if (! $onWifi) {
    echo PHP_EOL.'ONYO: '.$mac.' haiko kwenye WiFi. Unganisha simu kwanza.'.PHP_EOL;
}

$candidate = null;
$candidatePassword = '';

foreach ($users as $row) {
    if (str_starts_with($row['name'] ?? '', 'voucher_')) {
        $candidate = $row['name'];
        $candidatePassword = $row['password'] ?? '';
    }
}

if ($candidate === null) {
    echo PHP_EOL.'Hakuna mtumiaji wa vocha kwenye router. Ingiza vocha kwenye simu kwanza.'.PHP_EOL;

    exit(1);
}

echo PHP_EOL.'Naingiza '.$ip.' ('.$mac.') kama '.$candidate.'...'.PHP_EOL;

try {
    $client->query(
        (new RouterOS\Query('/ip/hotspot/active/login'))
            ->equal('user', $candidate)
            ->equal('password', $candidatePassword)
            ->equal('ip', $ip)
            ->equal('mac-address', $mac)
    )->read();

    echo 'Imefanikiwa.'.PHP_EOL;
} catch (Throwable $exception) {
    echo 'IMESHINDIKANA: '.trim($exception->getMessage()).PHP_EOL;
}

echo PHP_EOL.'=== Sessions zilizo hai ==='.PHP_EOL;

$active = $client->query('/ip/hotspot/active/print')->read();

if ($active === []) {
    echo '  (hakuna)'.PHP_EOL;
}

foreach ($active as $row) {
    echo sprintf(
        "  user=%-22s address=%-15s mac=%-19s uptime=%s\n",
        $row['user'] ?? '-',
        $row['address'] ?? '-',
        $row['mac-address'] ?? '-',
        $row['uptime'] ?? '-'
    );
}

echo PHP_EOL;
