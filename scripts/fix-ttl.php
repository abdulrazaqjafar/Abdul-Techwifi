<?php

/**
 * The upstream link drops forwarded traffic but carries the router's own
 * traffic fine. The only difference on the wire is the TTL: packets we forward
 * arrive one hop lower, which is how carriers detect and block tethering.
 * Rewriting the TTL on the way out makes client traffic indistinguishable from
 * the router's own.
 *
 * Run: php scripts/fix-ttl.php
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

$comment = 'Tech-Sphere TTL fix';

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    if (($row['comment'] ?? '') !== $comment || ! isset($row['.id'])) {
        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/firewall/mangle/remove'))->equal('.id', $row['.id'])
    )->read();

    echo 'Rule ya zamani imeondolewa.'.PHP_EOL;
}

$client->query(
    (new RouterOS\Query('/ip/firewall/mangle/add'))
        ->equal('chain', 'postrouting')
        ->equal('action', 'change-ttl')
        ->equal('new-ttl', 'set:64')
        ->equal('out-interface', 'ether1')
        ->equal('passthrough', 'yes')
        ->equal('comment', $comment)
)->read();

echo 'TTL fix imewekwa (kila packet inayotoka ether1 = TTL 64).'.PHP_EOL;

echo PHP_EOL.'=== Mangle rules ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    echo sprintf(
        "chain=%-12s action=%-12s new-ttl=%-8s new-mss=%-6s packets=%-8s comment=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['new-ttl'] ?? '-',
        $row['new-mss'] ?? '-',
        $row['packets'] ?? '-',
        $row['comment'] ?? '-'
    );
}

echo PHP_EOL.'Sasa jaribu tena kwenye simu: fungua neverssl.com.'.PHP_EOL;
