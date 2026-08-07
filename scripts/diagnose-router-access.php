<?php

/**
 * Diagnose why the billing server cannot reach the MikroTik API.
 * Run: php scripts/diagnose-router-access.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\RouterSetting::resolve();

echo '=== Router settings saved in the app ==='.PHP_EOL;
echo 'router_ip       : '.($settings->router_ip ?: '(empty)').PHP_EOL;
echo 'router_port     : '.($settings->router_port ?: '8728 (default)').PHP_EOL;
echo 'router_username : '.($settings->router_username ?: '(empty)').PHP_EOL;
echo 'password set    : '.($settings->router_password ? 'yes' : 'no').PHP_EOL;
echo 'MIKROTIK_BIND_IP: '.(config('services.mikrotik.bind_ip') ?: '(auto)').PHP_EOL;
echo PHP_EOL;

echo '=== Local IP addresses on this PC ==='.PHP_EOL;
foreach (gethostbynamel(gethostname()) ?: [] as $address) {
    echo $address.PHP_EOL;
}
echo PHP_EOL;

$router = $app->make(App\Services\MikrotikRouterService::class);

echo '=== API connection test ==='.PHP_EOL;
$result = $router->testConnection();
echo ($result['ok'] ? 'OK  : ' : 'FAIL: ').$result['message'].PHP_EOL.PHP_EOL;

if (! $result['ok']) {
    echo 'Rekebisha taarifa za router kwenye ukurasa /router kisha endesha script hii tena.'.PHP_EOL;
    exit(1);
}

try {
    $router->connect();

    echo '=== Hotspot hosts (vifaa vilivyounganishwa Wi-Fi) ==='.PHP_EOL;
    $hosts = $router->getHotspotHosts();

    if ($hosts === []) {
        echo '(hakuna kifaa)'.PHP_EOL;
    }

    foreach ($hosts as $host) {
        echo sprintf(
            "%-18s %-18s authorized=%s\n",
            $host['address'] ?? '-',
            $host['mac-address'] ?? '-',
            ($host['authorized'] ?? 'false')
        );
    }

    echo PHP_EOL.'=== Active hotspot sessions (wanaotumia internet) ==='.PHP_EOL;
    $sessions = $router->getOnlineUsers();

    if ($sessions === []) {
        echo '(hakuna session)'.PHP_EOL;
    }

    foreach ($sessions as $session) {
        echo sprintf(
            "%-20s %-18s %-18s uptime=%s\n",
            $session['user'] ?? '-',
            $session['address'] ?? '-',
            $session['mac-address'] ?? '-',
            $session['uptime'] ?? '-'
        );
    }
} finally {
    $router->disconnect();
}
