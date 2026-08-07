<?php

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

$files = $client->query('/file/print')->read();

usort($files, static fn (array $a, array $b): int => ((int) ($b['size'] ?? 0)) <=> ((int) ($a['size'] ?? 0)));

echo '=== Faili zinazotumia flash (kubwa kwanza) ==='.PHP_EOL;

foreach (array_slice($files, 0, 30) as $file) {
    echo sprintf("  %10s  %s\n", $file['size'] ?? '-', $file['name'] ?? '-');
}

echo 'Jumla: '.count($files).PHP_EOL;
