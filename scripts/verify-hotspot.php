<?php

/**
 * End-to-end check of the hotspot setup: internet uplink, captive portal,
 * walled garden, connected devices and the custom login page.
 * Run: php scripts/verify-hotspot.php
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
    'timeout' => 10,
    'attempts' => 2,
]);

$section = function (string $title): void {
    echo PHP_EOL.'=== '.$title.' ==='.PHP_EOL;
};

$dump = function (string $command, array $columns) use ($client): void {
    try {
        $rows = $client->query($command)->read();
    } catch (Throwable $exception) {
        echo 'ERROR: '.$exception->getMessage().PHP_EOL;

        return;
    }

    if ($rows === []) {
        echo '(hakuna)'.PHP_EOL;

        return;
    }

    foreach ($rows as $row) {
        $parts = [];

        foreach ($columns as $column) {
            if (array_key_exists($column, $row)) {
                $parts[] = $column.'='.$row[$column];
            }
        }

        echo ($parts === [] ? json_encode($row) : implode('  ', $parts)).PHP_EOL;
    }
};

$section('Default route (intaneti ipo?)');
$dump('/ip/route/print', ['dst-address', 'gateway', 'active', 'distance']);

$section('Router inaweza kufikia intaneti?');

try {
    $rows = $client->query(
        (new RouterOS\Query('/ping'))->equal('address', '8.8.8.8')->equal('count', '3')
    )->read();

    $last = end($rows) ?: [];
    echo 'sent='.($last['sent'] ?? '-').'  received='.($last['received'] ?? '-')
        .'  packet-loss='.($last['packet-loss'] ?? '-').'%'.PHP_EOL;
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

$section('Hotspot server');
$dump('/ip/hotspot/print', ['name', 'interface', 'profile', 'disabled', 'invalid']);

$section('Hotspot profile');
$dump('/ip/hotspot/profile/print', ['name', 'hotspot-address', 'html-directory', 'login-by']);

$section('Vifaa vilivyounganishwa WiFi (hotspot hosts)');
$dump('/ip/hotspot/host/print', ['mac-address', 'address', 'to-address', 'authorized', 'bypassed']);

$section('Sessions zinazotumia intaneti (hotspot active)');
$dump('/ip/hotspot/active/print', ['user', 'address', 'mac-address', 'uptime']);

$section('IP bindings (PC ya billing imepitishwa?)');
$dump('/ip/hotspot/ip-binding/print', ['address', 'mac-address', 'type', 'comment']);

$section('Walled garden (IP)');
$dump('/ip/hotspot/walled-garden/ip/print', ['dst-address', 'action', 'comment', 'disabled']);

$section('Walled garden (host)');
$dump('/ip/hotspot/walled-garden/print', ['dst-host', 'action', 'comment', 'disabled']);

$section('DHCP leases');
$dump('/ip/dhcp-server/lease/print', ['address', 'mac-address', 'host-name', 'status']);

$section('Watumiaji wa hotspot (vocha zilizosajiliwa)');
$dump('/ip/hotspot/user/print', ['name', 'profile', 'limit-uptime', 'comment']);

$section('Faili za login page kwenye router');
$dump('/file/print', ['name', 'type', 'size']);

$section('Vifurushi vya mfumo vs profiles za router');

$routerProfiles = [];

foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $row) {
    if (isset($row['name'])) {
        $routerProfiles[] = $row['name'];
    }
}

foreach (App\Models\Package::query()->get() as $package) {
    $profile = $package->profile_name;
    $status = $profile === null || $profile === ''
        ? 'HAKUNA profile_name'
        : (in_array($profile, $routerProfiles, true) ? 'OK' : 'HAIPO kwenye router');

    echo sprintf(
        "%-28s profile_name=%-24s %s\n",
        $package->name ?? '-',
        $profile ?: '-',
        $status
    );
}

echo PHP_EOL.'Profiles zilizopo kwenye router: '.implode(', ', $routerProfiles).PHP_EOL;

$section('Router inaweza kufikia billing server?');

try {
    $rows = $client->query(
        (new RouterOS\Query('/tool/fetch'))
            ->equal('url', 'http://192.168.20.253:8000/favicon.ico')
            ->equal('dst-path', 'billing-test.txt')
            ->equal('mode', 'http')
    )->read();

    echo 'OK: billing server inafikika kutoka router.'.PHP_EOL;
    echo json_encode(end($rows) ?: []).PHP_EOL;
} catch (Throwable $exception) {
    echo 'FAIL: '.$exception->getMessage().PHP_EOL;
    echo 'Hii ina maana Windows Firewall inazuia port 8000 kutoka 192.168.20.0/24.'.PHP_EOL;
}

echo PHP_EOL;
