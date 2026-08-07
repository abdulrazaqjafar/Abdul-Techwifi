<?php

/**
 * The upstream router treats this device far worse than a PC on the same
 * network: ICMP to the internet is dropped and HTTP takes 10-20 seconds. Give
 * the WAN port a fresh MAC so the upstream sees an unknown device, then measure
 * again. A big improvement means the upstream restricts us per device.
 *
 * Run: php scripts/change-wan-mac.php [mac]
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
    'timeout' => 30,
    'attempts' => 2,
]);

$newMac = strtoupper($argv[1] ?? 'D4:CA:6D:12:34:56');

$port = null;

foreach ($client->query('/interface/ethernet/print')->read() as $row) {
    if (($row['name'] ?? '') === 'ether1') {
        $port = $row;
    }
}

if ($port === null) {
    echo 'ether1 haikupatikana.'.PHP_EOL;

    exit(1);
}

echo 'MAC ya sasa   : '.($port['mac-address'] ?? '-').PHP_EOL;
echo 'MAC ya asili  : '.($port['orig-mac-address'] ?? $port['mac-address'] ?? '-').PHP_EOL;
echo 'MAC mpya      : '.$newMac.PHP_EOL.PHP_EOL;

$measure = function (string $label) use ($client): void {
    echo '--- '.$label.' ---'.PHP_EOL;

    try {
        $rows = $client->query(
            (new RouterOS\Query('/ping'))->equal('address', '8.8.8.8')->equal('count', '5')
        )->read();

        $last = end($rows) ?: [];

        echo sprintf(
            "  ping 8.8.8.8: received=%s/%s loss=%s%% avg=%s\n",
            $last['received'] ?? '-',
            $last['sent'] ?? '-',
            $last['packet-loss'] ?? '-',
            $last['avg-rtt'] ?? '-'
        );
    } catch (Throwable $exception) {
        echo '  ping ERROR: '.trim($exception->getMessage()).PHP_EOL;
    }

    $started = microtime(true);

    try {
        $client->query(
            (new RouterOS\Query('/tool/fetch'))
                ->equal('url', 'http://1.1.1.1/')
                ->equal('dst-path', 'mac-test.txt')
                ->equal('mode', 'http')
        )->read();

        echo sprintf("  fetch 1.1.1.1: OK katika %.1fs\n", microtime(true) - $started);
    } catch (Throwable $exception) {
        echo sprintf("  fetch 1.1.1.1: IMESHINDIKANA katika %.1fs\n", microtime(true) - $started);
    }
};

$measure('KABLA ya kubadilisha MAC');

$client->query(
    (new RouterOS\Query('/interface/ethernet/set'))
        ->equal('.id', $port['.id'])
        ->equal('mac-address', $newMac)
)->read();

echo PHP_EOL.'MAC imebadilishwa. Naomba IP mpya...'.PHP_EOL;

foreach ($client->query('/ip/dhcp-client/print')->read() as $row) {
    if (($row['interface'] ?? '') !== 'ether1' || ! isset($row['.id'])) {
        continue;
    }

    try {
        $client->query(
            (new RouterOS\Query('/ip/dhcp-client/release'))->equal('.id', $row['.id'])
        )->read();
    } catch (Throwable $exception) {
        echo 'release: '.trim($exception->getMessage()).PHP_EOL;
    }
}

sleep(20);

foreach ($client->query('/ip/dhcp-client/print')->read() as $row) {
    echo sprintf(
        '  status=%s address=%s gateway=%s',
        $row['status'] ?? '-',
        $row['address'] ?? '-',
        $row['gateway'] ?? '-'
    ).PHP_EOL;
}

echo PHP_EOL;

$measure('BAADA ya kubadilisha MAC');

echo PHP_EOL.'Kurudisha MAC ya asili: php scripts/change-wan-mac.php '.($port['orig-mac-address'] ?? $port['mac-address']).PHP_EOL;
