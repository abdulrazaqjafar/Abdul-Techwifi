<?php

/**
 * Work out why an authorised hotspot client has no internet, by reading the
 * traffic counters on the session, the NAT rule and the firewall rules.
 * Run: php scripts/diagnose-client-internet.php
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
    'timeout' => 15,
    'attempts' => 2,
]);

echo '=== Hotspot active (traffic ya mteja) ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/active/print')->read() as $row) {
    echo sprintf(
        "user=%-22s address=%-15s bytes-in=%-12s bytes-out=%-12s uptime=%s\n",
        $row['user'] ?? '-',
        $row['address'] ?? '-',
        $row['bytes-in'] ?? '-',
        $row['bytes-out'] ?? '-',
        $row['uptime'] ?? '-'
    );
}

echo PHP_EOL.'=== NAT rules (packets zinapita?) ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
    if (($row['dynamic'] ?? 'false') === 'true') {
        continue;
    }

    echo sprintf(
        "chain=%-8s action=%-12s out-interface=%-8s packets=%-10s bytes=%-12s disabled=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['out-interface'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-',
        $row['disabled'] ?? '-'
    );
}

echo PHP_EOL.'=== Firewall filter (rules zinazokataa) ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/filter/print')->read() as $row) {
    $action = $row['action'] ?? '-';

    if (! in_array($action, ['drop', 'reject'], true)) {
        continue;
    }

    echo sprintf(
        "chain=%-14s action=%-8s hotspot=%-14s packets=%-10s bytes=%s\n",
        $row['chain'] ?? '-',
        $action,
        $row['hotspot'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

echo PHP_EOL.'=== DNS ==='.PHP_EOL;

foreach ($client->query('/ip/dns/print')->read() as $row) {
    echo 'servers='.($row['servers'] ?? '-')
        .'  dynamic-servers='.($row['dynamic-servers'] ?? '-')
        .'  allow-remote-requests='.($row['allow-remote-requests'] ?? '-')
        .'  cache-used='.($row['cache-used'] ?? '-').PHP_EOL;
}

echo PHP_EOL.'=== Hotspot profile (kamili) ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/profile/print')->read() as $row) {
    if (($row['name'] ?? '') !== 'techsphere') {
        continue;
    }

    echo json_encode($row).PHP_EOL;
}

echo PHP_EOL.'=== Hotspot server (kamili) ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/print')->read() as $row) {
    echo json_encode($row).PHP_EOL;
}

echo PHP_EOL.'=== Connection tracking ya mteja 192.168.20.200 ==='.PHP_EOL;

$found = 0;

foreach ($client->query('/ip/firewall/connection/print')->read() as $row) {
    $src = $row['src-address'] ?? '';

    if (! str_starts_with($src, '192.168.20.200')) {
        continue;
    }

    if ($found++ >= 12) {
        break;
    }

    echo sprintf(
        "%-6s %-22s -> %-22s reply-dst=%-22s state=%s\n",
        $row['protocol'] ?? '-',
        $src,
        $row['dst-address'] ?? '-',
        $row['reply-dst-address'] ?? '-',
        $row['tcp-state'] ?? ($row['timeout'] ?? '-')
    );
}

if ($found === 0) {
    echo '(hakuna connection — traffic ya mteja haifiki router kabisa)'.PHP_EOL;
}

echo PHP_EOL;
