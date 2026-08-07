<?php

/**
 * Remove the scratch files the diagnostic fetches left on the router's storage.
 * Run: php scripts/cleanup-test-files.php
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

$scratch = [
    'billing-test.txt',
    'dns-test.txt',
    'reach-test.txt',
    'uplink-test.txt',
    'mac-test.txt',
];

foreach ($client->query('/file/print')->read() as $row) {
    $name = $row['name'] ?? '';

    if (! in_array($name, $scratch, true) || ! isset($row['.id'])) {
        continue;
    }

    try {
        $client->query(
            (new RouterOS\Query('/file/remove'))->equal('.id', $row['.id'])
        )->read();

        echo 'Imefutwa: '.$name.PHP_EOL;
    } catch (Throwable $exception) {
        echo 'IMESHINDIKANA ('.$name.'): '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo 'Imekamilika.'.PHP_EOL;
