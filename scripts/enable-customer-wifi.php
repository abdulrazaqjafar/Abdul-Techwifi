<?php

/**
 * Turn wlan1 into an open access point for hotspot customers.
 * Access control is done by the hotspot login, not by a WPA password.
 * Run: php scripts/enable-customer-wifi.php
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

$fields = ['name', 'ssid', 'mode', 'band', 'frequency', 'channel-width', 'security-profile', 'disabled', 'running'];

$show = function (string $label) use ($client, $fields): void {
    echo PHP_EOL.'=== '.$label.' ==='.PHP_EOL;

    foreach ($client->query('/interface/wireless/print')->read() as $row) {
        $parts = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $row)) {
                $parts[] = $field.'='.$row[$field];
            }
        }

        echo implode('  ', $parts).PHP_EOL;
    }
};

$show('Kabla');

$id = $client->query(
    (new RouterOS\Query('/interface/wireless/print'))->where('name', 'wlan1')
)->read()[0]['.id'] ?? null;

if ($id === null) {
    echo PHP_EOL.'wlan1 haipatikani.'.PHP_EOL;
    exit(1);
}

$client->query(
    (new RouterOS\Query('/interface/wireless/set'))
        ->equal('.id', $id)
        ->equal('mode', 'ap-bridge')
        ->equal('ssid', 'Abdul-Tech')
        ->equal('security-profile', 'default')
        ->equal('disabled', 'no')
)->read();

sleep(6);

$show('Baada');

echo PHP_EOL.'=== Registration table (simu zilizojiunga) ==='.PHP_EOL;

$registered = $client->query('/interface/wireless/registration-table/print')->read();

if ($registered === []) {
    echo '(hakuna kifaa bado)'.PHP_EOL;
}

foreach ($registered as $row) {
    echo sprintf("%-19s interface=%s\n", $row['mac-address'] ?? '-', $row['interface'] ?? '-');
}

echo PHP_EOL;
