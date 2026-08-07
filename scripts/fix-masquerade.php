<?php

/**
 * Client traffic was leaving the router without being source-NATed, so replies
 * never came back. The rule is replaced (not edited) because "set" is silently
 * ignored on this RouterOS build, and it now matches the LAN subnet instead of
 * the WAN interface name.
 *
 * Run: php scripts/fix-masquerade.php
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

$srcnatRules = function () use ($client): array {
    $rules = [];

    foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
        if (($row['chain'] ?? '') === 'srcnat') {
            $rules[] = $row;
        }
    }

    return $rules;
};

$show = function (string $label) use ($srcnatRules): void {
    echo PHP_EOL.'=== '.$label.' ==='.PHP_EOL;

    foreach ($srcnatRules() as $row) {
        echo sprintf(
            "id=%-5s action=%-12s src=%-18s out-int=%-8s packets=%-8s bytes=%s\n",
            $row['.id'] ?? '-',
            $row['action'] ?? '-',
            $row['src-address'] ?? '-',
            $row['out-interface'] ?? '-',
            $row['packets'] ?? '-',
            $row['bytes'] ?? '-'
        );
    }
};

$show('srcnat kabla');

foreach ($srcnatRules() as $row) {
    if (($row['action'] ?? '') !== 'masquerade' || ($row['dynamic'] ?? 'false') === 'true') {
        continue;
    }

    $client->query(
        (new RouterOS\Query('/ip/firewall/nat/remove'))->equal('.id', $row['.id'])
    )->read();

    echo PHP_EOL.'Rule ya zamani '.$row['.id'].' imeondolewa.'.PHP_EOL;
}

$client->query(
    (new RouterOS\Query('/ip/firewall/nat/add'))
        ->equal('chain', 'srcnat')
        ->equal('action', 'masquerade')
        ->equal('src-address', '192.168.20.0/24')
        ->equal('comment', 'Tech-Sphere internet sharing')
)->read();

echo 'Rule mpya imeongezwa (src-address=192.168.20.0/24, bila out-interface).'.PHP_EOL;

$show('srcnat baada');

echo PHP_EOL.'Inasubiri sekunde 25 ili traffic mpya ipite...'.PHP_EOL;
sleep(25);

$show('srcnat baada ya sekunde 25');

echo PHP_EOL.'=== Filter rules zinazogusa traffic (forward) ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/filter/print')->read() as $row) {
    $packets = (int) ($row['packets'] ?? 0);

    if ($packets === 0) {
        continue;
    }

    echo sprintf(
        "chain=%-14s action=%-8s hotspot=%-16s packets=%-8s bytes=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['hotspot'] ?? '-',
        $packets,
        $row['bytes'] ?? '-'
    );
}

echo PHP_EOL.'=== Connection tracking ya mteja (NAT inafanyika?) ==='.PHP_EOL;

$found = 0;

foreach ($client->query('/ip/firewall/connection/print')->read() as $row) {
    $src = $row['src-address'] ?? '';
    $dst = $row['dst-address'] ?? '';

    if (! str_starts_with($src, '192.168.20.2') || str_starts_with($dst, '192.168.20.')) {
        continue;
    }

    if ($found++ >= 10) {
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
    echo '(hakuna connection ya nje kwa sasa — fungua ukurasa kwenye simu kisha rudia)'.PHP_EOL;
}

echo PHP_EOL;
