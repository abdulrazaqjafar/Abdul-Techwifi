<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RouterSetting;
use RouterOS\Client;

$settings = RouterSetting::current();

if (! $settings) {
    echo "Hakuna router settings kwenye database.\n";
    exit(1);
}

echo "IP: {$settings->router_ip}\n";
echo "Port: {$settings->router_port}\n";
echo "User: {$settings->router_username}\n";
echo "Has password: ".(filled($settings->router_password) ? 'yes' : 'no')."\n\n";

$config = [
    'host' => $settings->router_ip,
    'port' => $settings->router_port ?: 8728,
    'user' => $settings->router_username,
    'pass' => $settings->router_password,
    'timeout' => 10,
    'attempts' => 2,
    'delay' => 1,
];

$bindIp = config('services.mikrotik.bind_ip') ?: '192.168.10.254';
$config['socket_options'] = ['bindto' => $bindIp.':0'];

try {
    $client = new Client($config);
    $result = $client->query('/system/resource/print')->read();
    echo "SUCCESS! RouterOS version: ".($result[0]['version'] ?? 'unknown')."\n";
} catch (Throwable $e) {
    echo "FAIL: ".$e->getMessage()."\n";
    exit(1);
}
