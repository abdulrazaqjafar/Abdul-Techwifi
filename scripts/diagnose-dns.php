<?php

/**
 * The router's DNS server does not answer LAN clients. Find out whether the
 * resolver itself is broken or whether the input firewall is dropping the
 * queries.
 * Run: php scripts/diagnose-dns.php
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

echo '=== /ip dns ==='.PHP_EOL;

foreach ($client->query('/ip/dns/print')->read() as $row) {
    echo json_encode($row).PHP_EOL;
}

echo PHP_EOL.'=== Resolver ya router yenyewe (jina jipya, bila cache) ==='.PHP_EOL;

foreach (['http://example.com/', 'http://www.wikipedia.org/'] as $url) {
    try {
        $client->query(
            (new RouterOS\Query('/tool/fetch'))
                ->equal('url', $url)
                ->equal('dst-path', 'dns-test.txt')
                ->equal('mode', 'http')
        )->read();

        echo 'OK  : '.$url.PHP_EOL;
    } catch (Throwable $exception) {
        echo 'FAIL: '.$url.' — '.$exception->getMessage().PHP_EOL;
    }
}

echo PHP_EOL.'=== /ip dns cache ==='.PHP_EOL;

try {
    $rows = $client->query('/ip/dns/cache/print')->read();

    echo 'rekodi: '.count($rows).PHP_EOL;

    foreach (array_slice($rows, 0, 10) as $row) {
        echo sprintf("  %-40s %s\n", $row['name'] ?? '-', $row['address'] ?? ($row['data'] ?? '-'));
    }
} catch (Throwable $exception) {
    echo 'ERROR: '.$exception->getMessage().PHP_EOL;
}

echo PHP_EOL.'=== input chain (nini inazuia?) ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/filter/print')->read() as $row) {
    if (($row['chain'] ?? '') !== 'input' && ($row['chain'] ?? '') !== 'hs-input') {
        continue;
    }

    echo sprintf(
        "chain=%-9s action=%-8s hotspot=%-18s protocol=%-5s dst-port=%-6s packets=%-9s bytes=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['hotspot'] ?? '-',
        $row['protocol'] ?? '-',
        $row['dst-port'] ?? '-',
        $row['packets'] ?? '-',
        $row['bytes'] ?? '-'
    );
}

echo PHP_EOL.'=== hotspot ip-binding ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/ip-binding/print')->read() as $row) {
    echo json_encode($row).PHP_EOL;
}

echo PHP_EOL;
