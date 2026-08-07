<?php

/**
 * Sample the client's external connections while somebody browses on the phone,
 * so we can see whether source NAT is applied and whether replies come back.
 * Run: php scripts/watch-client-traffic.php
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

for ($round = 1; $round <= 3; $round++) {
    echo PHP_EOL.'================ Sampuli '.$round.' ================'.PHP_EOL;

    foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
        if (($row['action'] ?? '') !== 'masquerade') {
            continue;
        }

        echo 'masquerade: connections mpya='.($row['packets'] ?? '-').PHP_EOL;
    }

    foreach ($client->query('/ip/hotspot/active/print')->read() as $row) {
        echo sprintf(
            "session %-22s bytes-in=%-12s bytes-out=%s\n",
            $row['user'] ?? '-',
            $row['bytes-in'] ?? '-',
            $row['bytes-out'] ?? '-'
        );
    }

    $external = 0;
    $nated = 0;
    $established = 0;

    echo PHP_EOL.'Connections za nje:'.PHP_EOL;

    foreach ($client->query('/ip/firewall/connection/print')->read() as $row) {
        $src = $row['src-address'] ?? '';
        $dst = $row['dst-address'] ?? '';

        if (! $isLan($src) || $isLan($dst)) {
            continue;
        }

        $external++;
        $replyDst = $row['reply-dst-address'] ?? '';
        $isNated = str_starts_with($replyDst, '192.168.1.') && ! str_starts_with($replyDst, '192.168.1.1:');

        if (str_starts_with($replyDst, '192.168.1.134')) {
            $nated++;
        }

        $state = $row['tcp-state'] ?? '-';

        if ($state === 'established') {
            $established++;
        }

        if ($external <= 8) {
            echo sprintf(
                "  %-5s %-22s -> %-22s reply-dst=%-22s state=%-12s %s\n",
                $row['protocol'] ?? '-',
                $src,
                $dst,
                $replyDst ?: '-',
                $state,
                str_starts_with($replyDst, '192.168.1.134') ? 'NAT OK' : 'HAKUNA NAT'
            );
        }
    }

    if ($external === 0) {
        echo '  (hakuna connection ya nje kwa sasa)'.PHP_EOL;
    } else {
        echo sprintf(
            "  jumla=%d  zenye NAT=%d  established=%d\n",
            $external,
            $nated,
            $established
        );
    }

    if ($round < 3) {
        sleep(30);
    }
}

echo PHP_EOL;
