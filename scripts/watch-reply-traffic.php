<?php

/**
 * Decide where the client's traffic dies, by comparing the two directions of
 * each connection:
 *   repl-packets = 0  -> replies never reach the router (upstream or NAT issue)
 *   repl-packets > 0  -> replies arrive but something drops them on the way back
 *
 * Run: php scripts/watch-reply-traffic.php
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
    'timeout' => 20,
    'attempts' => 2,
]);

$isLan = static fn (string $address): bool => str_starts_with($address, '192.168.20.');

$interfaceStats = function () use ($client): void {
    foreach ($client->query('/interface/print')->read() as $row) {
        $name = $row['name'] ?? '';

        if (! in_array($name, ['ether1', 'bridge-LAN'], true)) {
            continue;
        }

        echo sprintf(
            "  %-12s rx-byte=%-14s tx-byte=%-14s rx-packet=%-10s tx-packet=%s\n",
            $name,
            $row['rx-byte'] ?? '-',
            $row['tx-byte'] ?? '-',
            $row['rx-packet'] ?? '-',
            $row['tx-packet'] ?? '-'
        );
    }
};

for ($round = 1; $round <= 2; $round++) {
    echo PHP_EOL.'================ Sampuli '.$round.' ================'.PHP_EOL;

    echo 'Interface counters:'.PHP_EOL;
    $interfaceStats();

    echo PHP_EOL.'Connections za nje (pande zote mbili):'.PHP_EOL;

    $shown = 0;
    $withReplies = 0;
    $total = 0;

    foreach ($client->query('/ip/firewall/connection/print')->read() as $row) {
        $src = $row['src-address'] ?? '';
        $dst = $row['dst-address'] ?? '';

        if (! $isLan($src) || $isLan($dst)) {
            continue;
        }

        $total++;
        $replPackets = (int) ($row['repl-packets'] ?? 0);

        if ($replPackets > 0) {
            $withReplies++;
        }

        if ($shown++ < 10) {
            echo sprintf(
                "  %-5s %-22s -> %-22s toka=%-6s rudi=%-6s state=%s\n",
                $row['protocol'] ?? '-',
                $src,
                $dst,
                $row['orig-packets'] ?? '-',
                $row['repl-packets'] ?? '-',
                $row['tcp-state'] ?? '-'
            );
        }
    }

    echo sprintf('  jumla=%d  zenye majibu=%d', $total, $withReplies).PHP_EOL;

    if ($round < 2) {
        sleep(40);
    }
}

echo PHP_EOL;
