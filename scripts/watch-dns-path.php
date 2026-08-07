<?php

/**
 * Show whether the client's DNS queries are redirected to the router's own
 * resolver or forwarded upstream, and whether forwarded traffic is NATed.
 *
 * reply-src = 192.168.20.1  -> redirected to the router (hotspot intercept)
 * reply-src = the original destination -> forwarded upstream
 *
 * Run: php scripts/watch-dns-path.php
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

for ($round = 1; $round <= 2; $round++) {
    echo PHP_EOL.'================ Sampuli '.$round.' ================'.PHP_EOL;

    foreach ($client->query('/ip/firewall/connection/print')->read() as $row) {
        $src = $row['src-address'] ?? '';
        $dst = $row['dst-address'] ?? '';

        if (! $isLan($src) || $isLan($dst)) {
            continue;
        }

        $replySrc = $row['reply-src-address'] ?? '-';
        $replyDst = $row['reply-dst-address'] ?? '-';

        $path = str_starts_with($replySrc, '192.168.20.1:')
            ? 'IMEELEKEZWA kwa router'
            : (str_starts_with($replyDst, '192.168.1.134') ? 'IMETOKA na NAT' : 'IMETOKA BILA NAT');

        echo sprintf(
            "%-5s %-22s -> %-22s reply-src=%-22s %s\n",
            $row['protocol'] ?? '-',
            $src,
            $dst,
            $replySrc,
            $path
        );
    }

    echo PHP_EOL.'DNS cache: ';

    try {
        echo count($client->query('/ip/dns/cache/print')->read()).' rekodi'.PHP_EOL;
    } catch (Throwable $exception) {
        echo 'ERROR'.PHP_EOL;
    }

    foreach ($client->query('/ip/hotspot/active/print')->read() as $row) {
        echo sprintf(
            "session %-22s bytes-in=%-12s bytes-out=%s\n",
            $row['user'] ?? '-',
            $row['bytes-in'] ?? '-',
            $row['bytes-out'] ?? '-'
        );
    }

    if ($round < 2) {
        sleep(35);
    }
}

echo PHP_EOL;
