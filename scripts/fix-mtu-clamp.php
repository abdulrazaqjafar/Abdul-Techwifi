<?php

/**
 * The upstream network drops ICMP, so path-MTU discovery cannot work and large
 * packets from clients vanish: handshakes succeed, then pages stall forever.
 * Clamping the TCP MSS on SYN packets keeps every segment small enough to pass.
 *
 * Also removes the temporary traffic probes left by add-traffic-probes.php.
 *
 * Run: php scripts/fix-mtu-clamp.php
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

$clampComment = 'Tech-Sphere MSS clamp';
$stale = ['probe-prerouting', 'probe-postrouting', $clampComment];

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    if (! in_array($row['comment'] ?? '', $stale, true) || ! isset($row['.id'])) {
        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/firewall/mangle/remove'))->equal('.id', $row['.id'])
    )->read();

    echo 'Imeondolewa: '.$row['comment'].PHP_EOL;
}

$client->query(
    (new RouterOS\Query('/ip/firewall/mangle/add'))
        ->equal('chain', 'forward')
        ->equal('action', 'change-mss')
        ->equal('new-mss', '1400')
        ->equal('protocol', 'tcp')
        ->equal('tcp-flags', 'syn')
        ->equal('tcp-mss', '1401-65535')
        ->equal('passthrough', 'yes')
        ->equal('comment', $clampComment)
)->read();

echo 'MSS clamp imewekwa (new-mss=1400).'.PHP_EOL;

echo PHP_EOL.'=== Mangle rules za sasa ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/mangle/print')->read() as $row) {
    echo sprintf(
        "chain=%-12s action=%-12s new-mss=%-6s comment=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['new-mss'] ?? '-',
        $row['comment'] ?? '-'
    );
}

echo PHP_EOL.'=== Router yenyewe inafikia nini? ==='.PHP_EOL;

foreach (['http://1.1.1.1/', 'http://neverssl.com/', 'http://connectivitycheck.gstatic.com/generate_204'] as $url) {
    try {
        $client->query(
            (new RouterOS\Query('/tool/fetch'))
                ->equal('url', $url)
                ->equal('dst-path', 'reach-test.txt')
                ->equal('mode', 'http')
        )->read();

        echo 'OK  : '.$url.PHP_EOL;
    } catch (Throwable $exception) {
        echo 'FAIL: '.$url.' — '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo PHP_EOL.'Sasa kwenye simu zima WiFi, iwashe, na ufungue ukurasa wowote.'.PHP_EOL;
