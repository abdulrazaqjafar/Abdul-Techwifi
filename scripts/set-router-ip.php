<?php

/**
 * Update the router IP the billing system talks to.
 * Run: php scripts/set-router-ip.php 192.168.20.1
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ip = $argv[1] ?? null;

if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
    echo 'Tumia: php scripts/set-router-ip.php 192.168.20.1'.PHP_EOL;
    exit(1);
}

$settings = App\Models\RouterSetting::resolve();
$old = $settings->router_ip;

$settings->update(['router_ip' => $ip]);

echo 'router_ip: '.($old ?: '(empty)').' -> '.$ip.PHP_EOL;

$result = $app->make(App\Services\MikrotikRouterService::class)->testConnection();

echo ($result['ok'] ? 'OK  : ' : 'FAIL: ').$result['message'].PHP_EOL;
