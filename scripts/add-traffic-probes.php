<?php

/**
 * Count the client's packets at two points in the router's pipeline so we can
 * tell whether they survive routing and filtering:
 *   prerouting  = packet arrived at the router
 *   postrouting = packet is about to leave, this is where masquerade runs
 *
 * Run: php scripts/add-traffic-probes.php   (then browse on the phone)
 *      php scripts/read-traffic-probes.php  (to read the counters)
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

$probes = [
    'probe-prerouting' => 'prerouting',
    'probe-postrouting' => 'postrouting',
];

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    if (! isset($probes[$row['comment'] ?? '']) || ! isset($row['.id'])) {
        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/firewall/mangle/remove'))->equal('.id', $row['.id'])
    )->read();
}

foreach ($probes as $comment => $chain) {
    $client->query(
        (new RouterOS\Query('/ip/firewall/mangle/add'))
            ->equal('chain', $chain)
            ->equal('action', 'passthrough')
            ->equal('src-address', '192.168.20.0/24')
            ->equal('dst-address-list', '')
            ->equal('comment', $comment)
    )->read();

    echo 'Kipimo kimewekwa: '.$comment.' ('.$chain.')'.PHP_EOL;
}

echo PHP_EOL.'Sasa fungua ukurasa wowote kwenye simu kwa sekunde 30.'.PHP_EOL;
