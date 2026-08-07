<?php

/**
 * Teach the router to answer for tech-spherewifi.xyz itself, pointing at the
 * billing server on the LAN.
 *
 * Two reasons this lives on the router rather than only in public DNS:
 *   - Clients resolve the portal even when the upstream link is down.
 *   - Traffic stays inside the LAN instead of leaving and coming back.
 *
 * Run: php scripts/setup-domain-dns.php
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

$serverIp = '192.168.20.253';
$names = ['tech-spherewifi.xyz', 'www.tech-spherewifi.xyz'];

echo '=== Static DNS kwenye router ==='.PHP_EOL;

$existing = $client->query('/ip/dns/static/print')->read();

foreach ($names as $name) {
    foreach ($existing as $row) {
        if (($row['name'] ?? '') !== $name || ! isset($row['.id'])) {
            continue;
        }

        $client->query(
            (new RouterOS\Query('/ip/dns/static/remove'))->equal('.id', $row['.id'])
        )->read();
    }

    $client->query(
        (new RouterOS\Query('/ip/dns/static/add'))
            ->equal('name', $name)
            ->equal('address', $serverIp)
            ->equal('ttl', '1d')
            ->equal('comment', 'Tech-Sphere portal')
    )->read();

    echo '  '.$name.' -> '.$serverIp.PHP_EOL;
}

echo PHP_EOL.'=== Walled garden kwa jina ==='.PHP_EOL;

$gardenHosts = $client->query('/ip/hotspot/walled-garden/print')->read();

foreach ($names as $name) {
    $alreadyAllowed = false;

    foreach ($gardenHosts as $row) {
        $alreadyAllowed = $alreadyAllowed || ($row['dst-host'] ?? '') === $name;
    }

    if ($alreadyAllowed) {
        echo '  '.$name.' ipo tayari.'.PHP_EOL;

        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/hotspot/walled-garden/add'))
            ->equal('dst-host', $name)
            ->equal('action', 'allow')
            ->equal('comment', 'Tech-Sphere portal')
    )->read();

    echo '  '.$name.' imeruhusiwa.'.PHP_EOL;
}

echo PHP_EOL.'=== Hali ya sasa ==='.PHP_EOL;

foreach ($client->query('/ip/dns/static/print')->read() as $row) {
    echo sprintf(
        "  %-30s -> %-16s ttl=%s\n",
        $row['name'] ?? '-',
        $row['address'] ?? '-',
        $row['ttl'] ?? '-'
    );
}

foreach ($client->query('/ip/hotspot/walled-garden/print')->read() as $row) {
    echo sprintf(
        "  walled-garden host=%-30s action=%s\n",
        $row['dst-host'] ?? '-',
        $row['action'] ?? '-'
    );
}

// The router resolves names through the same DNS server the clients use, so a
// successful fetch by name proves both the DNS answer and the route to the
// billing server.
echo PHP_EOL.'=== Jina linafanya kazi? ==='.PHP_EOL;

try {
    $client->query(
        (new RouterOS\Query('/tool/fetch'))
            ->equal('url', 'http://tech-spherewifi.xyz:8000/')
            ->equal('dst-path', 'domain-test.txt')
            ->equal('mode', 'http')
    )->read();

    echo '  OK — router imefungua ukurasa kwa jina la domain.'.PHP_EOL;

    foreach ($client->query('/file/print')->read() as $row) {
        if (($row['name'] ?? '') !== 'domain-test.txt' || ! isset($row['.id'])) {
            continue;
        }

        echo '  Ukubwa wa ukurasa: '.($row['size'] ?? '-').' bytes'.PHP_EOL;

        $client->query(
            (new RouterOS\Query('/file/remove'))->equal('.id', $row['.id'])
        )->read();
    }
} catch (Throwable $exception) {
    echo '  IMESHINDIKANA: '.trim($exception->getMessage()).PHP_EOL;
}

echo PHP_EOL;
