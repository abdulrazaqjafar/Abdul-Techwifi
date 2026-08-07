<?php

/**
 * Confirm the router's basic shape: which interfaces are bridged, who is
 * currently associated on WiFi, and which hotspot sessions are alive.
 * A WAN port sitting inside the LAN bridge would merge both networks and
 * explain traffic appearing without any matching client connection.
 *
 * Run: php scripts/check-topology.php
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

echo '=== Bridge ports ==='.PHP_EOL;

foreach ($client->query('/interface/bridge/port/print')->read() as $row) {
    echo sprintf(
        "  interface=%-12s bridge=%-14s disabled=%s\n",
        $row['interface'] ?? '-',
        $row['bridge'] ?? '-',
        $row['disabled'] ?? '-'
    );
}

echo PHP_EOL.'=== Wireless registration (nani yupo kwenye WiFi) ==='.PHP_EOL;

$registrations = $client->query('/interface/wireless/registration-table/print')->read();

if ($registrations === []) {
    echo '  (hakuna kifaa kilichounganishwa)'.PHP_EOL;
}

foreach ($registrations as $row) {
    echo sprintf(
        "  mac=%-19s interface=%-8s signal=%-6s uptime=%s\n",
        $row['mac-address'] ?? '-',
        $row['interface'] ?? '-',
        $row['signal-strength'] ?? '-',
        $row['uptime'] ?? '-'
    );
}

echo PHP_EOL.'=== Hotspot active ==='.PHP_EOL;

$active = $client->query('/ip/hotspot/active/print')->read();

if ($active === []) {
    echo '  (hakuna session)'.PHP_EOL;
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

echo PHP_EOL.'=== DHCP leases ==='.PHP_EOL;

foreach ($client->query('/ip/dhcp-server/lease/print')->read() as $row) {
    echo sprintf(
        "  %-15s %-19s %-22s status=%s\n",
        $row['address'] ?? '-',
        $row['mac-address'] ?? '-',
        $row['host-name'] ?? '-',
        $row['status'] ?? '-'
    );
}

echo PHP_EOL.'=== Traffic kubwa inatoka wapi? (connections zote) ==='.PHP_EOL;

$rows = $client->query('/ip/firewall/connection/print')->read();

echo '  jumla ya connections: '.count($rows).PHP_EOL;

usort($rows, static fn ($a, $b) => (int) ($b['repl-bytes'] ?? 0) <=> (int) ($a['repl-bytes'] ?? 0));

foreach (array_slice($rows, 0, 8) as $row) {
    echo sprintf(
        "  %-5s %-22s -> %-22s toka-bytes=%-10s rudi-bytes=%s\n",
        $row['protocol'] ?? '-',
        $row['src-address'] ?? '-',
        $row['dst-address'] ?? '-',
        $row['orig-bytes'] ?? '-',
        $row['repl-bytes'] ?? '-'
    );
}

echo PHP_EOL;
