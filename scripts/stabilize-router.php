<?php

/**
 * Stop the API connection from dropping.
 *
 * The router carries 192.168.10.1/24 on both ether1 (facing the upstream
 * network, which also uses 192.168.10.0/24) and bridge-LAN (facing the billing
 * PC). It therefore resolves 192.168.10.253 on whichever side answers first,
 * so replies to the billing PC intermittently go to the wrong segment.
 *
 * This detaches the upstream side completely: ether1 loses its address and
 * leaves bridge1, and bridge1 plus its DHCP server are removed.
 *
 * Run: php scripts/stabilize-router.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\RouterSetting::resolve();

$config = [
    'host' => $settings->router_ip,
    'port' => $settings->router_port ?: 8728,
    'user' => $settings->router_username,
    'pass' => $settings->router_password,
    'timeout' => 4,
    'attempts' => 1,
];

$client = null;

for ($attempt = 1; $attempt <= 40; $attempt++) {
    try {
        $client = new RouterOS\Client($config);
        echo 'Muunganisho umepatikana kwenye jaribio la '.$attempt.'.'.PHP_EOL;

        break;
    } catch (Throwable $exception) {
        echo 'Jaribio '.$attempt.': bado haifikiki...'.PHP_EOL;
        sleep(3);
    }
}

if ($client === null) {
    echo PHP_EOL.'Imeshindwa kufikia router. Tumia Winbox kwa MAC address.'.PHP_EOL;
    exit(1);
}

/**
 * Remove every item returned by a print command, ignoring an empty result.
 */
$removeAll = function (string $path, array $where = []) use ($client): int {
    $query = new RouterOS\Query($path.'/print');

    foreach ($where as $field => $value) {
        $query->where($field, $value);
    }

    $rows = $client->query($query)->read();
    $removed = 0;

    foreach ($rows as $row) {
        if (! isset($row['.id'])) {
            continue;
        }

        $client->query(
            (new RouterOS\Query($path.'/remove'))->equal('.id', $row['.id'])
        )->read();

        $removed++;
    }

    return $removed;
};

echo PHP_EOL.'Inaondoa IP ya ether1... ';
echo $removeAll('/ip/address', ['interface' => 'ether1']).' imeondolewa'.PHP_EOL;

echo 'Inaondoa DHCP server dhcp1... ';
echo $removeAll('/ip/dhcp-server', ['name' => 'dhcp1']).' imeondolewa'.PHP_EOL;

echo 'Inaondoa port za bridge1... ';
echo $removeAll('/interface/bridge/port', ['bridge' => 'bridge1']).' imeondolewa'.PHP_EOL;

echo 'Inaondoa bridge1... ';
echo $removeAll('/interface/bridge', ['name' => 'bridge1']).' imeondolewa'.PHP_EOL;

echo 'Inaondoa pool "dhcp" isiyotumika... ';
echo $removeAll('/ip/pool', ['name' => 'dhcp']).' imeondolewa'.PHP_EOL;

echo 'Inaondoa ARP zilizopitwa na wakati... ';
echo $removeAll('/ip/arp', ['dynamic' => 'true']).' imeondolewa'.PHP_EOL;

echo PHP_EOL.'=== /ip/address ==='.PHP_EOL;

foreach ($client->query('/ip/address/print')->read() as $row) {
    echo sprintf("%-19s interface=%s\n", $row['address'] ?? '-', $row['interface'] ?? '-');
}

echo PHP_EOL.'=== /interface/wireless ==='.PHP_EOL;

foreach ($client->query('/interface/wireless/print')->read() as $row) {
    echo sprintf(
        "%-7s ssid=%-16s disabled=%-6s running=%s\n",
        $row['name'] ?? '-',
        $row['ssid'] ?? '-',
        $row['disabled'] ?? '-',
        $row['running'] ?? '-'
    );
}

echo PHP_EOL.'=== /log (mistari 25 ya mwisho) ==='.PHP_EOL;

$entries = $client->query('/log/print')->read();

foreach (array_slice($entries, -25) as $row) {
    echo sprintf("%-10s %-20s %s\n", $row['time'] ?? '-', $row['topics'] ?? '-', $row['message'] ?? '-');
}

echo PHP_EOL;
