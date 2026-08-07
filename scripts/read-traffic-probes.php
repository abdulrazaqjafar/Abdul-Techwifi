<?php

/**
 * Read the traffic probes added by add-traffic-probes.php, plus the masquerade
 * counters and a sample of the client's connections.
 * Run: php scripts/read-traffic-probes.php
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

echo '=== Vipimo vya traffic ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    $comment = $row['comment'] ?? '';

    if (! str_starts_with($comment, 'probe-')) {
        continue;
    }

    echo sprintf(
        "%-20s chain=%-12s packets=%-10s bytes=%s\n",
        $comment,
        $row['chain'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

echo PHP_EOL.'=== Masquerade ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
    if (($row['action'] ?? '') !== 'masquerade') {
        continue;
    }

    echo sprintf(
        "src=%-18s out-int=%-8s packets=%-10s bytes=%s\n",
        $row['src-address'] ?? '-',
        $row['out-interface'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

echo PHP_EOL.'=== Sessions ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/active/print')->read() as $row) {
    echo sprintf(
        "user=%-22s address=%-15s bytes-in=%-12s bytes-out=%s\n",
        $row['user'] ?? '-',
        $row['address'] ?? '-',
        $row['bytes-in'] ?? '-',
        $row['bytes-out'] ?? '-'
    );
}

echo PHP_EOL.'=== Connections za nje ==='.PHP_EOL;

$found = 0;

foreach ($client->query('/ip/firewall/connection/print')->read() as $row) {
    $src = $row['src-address'] ?? '';
    $dst = $row['dst-address'] ?? '';

    if (! str_starts_with($src, '192.168.20.') || str_starts_with($dst, '192.168.20.')) {
        continue;
    }

    if ($found++ >= 12) {
        break;
    }

    $replyDst = $row['reply-dst-address'] ?? '-';

    echo sprintf(
        "%-6s %-22s -> %-22s reply-dst=%-22s %s\n",
        $row['protocol'] ?? '-',
        $src,
        $dst,
        $replyDst,
        str_starts_with($replyDst, '192.168.1.') ? 'NAT OK' : 'HAKUNA NAT'
    );
}

if ($found === 0) {
    echo '(hakuna connection ya nje)'.PHP_EOL;
}

echo PHP_EOL;
