<?php

/**
 * Put the custom login page in place and work out why the router has no
 * internet even though a default route exists.
 * Run: php scripts/finalize-hotspot.php
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

$fileId = function (string $name) use ($client): ?string {
    $rows = $client->query(
        (new RouterOS\Query('/file/print'))->where('name', $name)
    )->read();

    return $rows[0]['.id'] ?? null;
};

echo '=== Kuweka login.html sehemu sahihi ==='.PHP_EOL;

$uploaded = $fileId('login.html');

if ($uploaded === null) {
    echo 'login.html haipo kwenye mzizi wa router — inawezekana imekwisha kuhamishwa.'.PHP_EOL;
} else {
    $original = $fileId('flash/hotspot/login.html');

    if ($original !== null) {
        $backup = $fileId('flash/hotspot/login-mikrotik.html');

        if ($backup === null) {
            $client->query(
                (new RouterOS\Query('/file/set'))
                    ->equal('.id', $original)
                    ->equal('name', 'flash/hotspot/login-mikrotik.html')
            )->read();

            echo 'Ukurasa wa asili umehifadhiwa kama flash/hotspot/login-mikrotik.html'.PHP_EOL;
        } else {
            $client->query(
                (new RouterOS\Query('/file/remove'))->equal('.id', $original)
            )->read();

            echo 'Ukurasa wa asili umeondolewa (backup ilikuwa tayari ipo).'.PHP_EOL;
        }
    }

    $client->query(
        (new RouterOS\Query('/file/set'))
            ->equal('.id', $uploaded)
            ->equal('name', 'flash/hotspot/login.html')
    )->read();

    echo 'login.html imehamishwa kwenda flash/hotspot/login.html'.PHP_EOL;
}

echo PHP_EOL.'=== Faili za hotspot sasa ==='.PHP_EOL;

foreach ($client->query('/file/print')->read() as $row) {
    $name = $row['name'] ?? '';

    if (! str_contains($name, 'login')) {
        continue;
    }

    echo sprintf("%-40s size=%s\n", $name, $row['size'] ?? '-');
}

echo PHP_EOL.'=== Uchunguzi wa intaneti ==='.PHP_EOL;

$ping = function (string $target) use ($client): void {
    try {
        $rows = $client->query(
            (new RouterOS\Query('/ping'))
                ->equal('address', $target)
                ->equal('count', '3')
        )->read();

        $last = end($rows) ?: [];

        echo sprintf(
            "%-16s sent=%s received=%s loss=%s%%\n",
            $target,
            $last['sent'] ?? '-',
            $last['received'] ?? '-',
            $last['packet-loss'] ?? '-'
        );
    } catch (Throwable $exception) {
        echo sprintf("%-16s ERROR: %s\n", $target, $exception->getMessage());
    }
};

$ping('192.168.1.1');
$ping('192.168.1.134');
$ping('8.8.8.8');
$ping('1.1.1.1');

echo PHP_EOL.'=== NAT rules ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/nat/print')->read() as $row) {
    echo sprintf(
        "chain=%-8s action=%-12s out-interface=%-10s src-address=%-16s disabled=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['out-interface'] ?? '-',
        $row['src-address'] ?? '-',
        $row['disabled'] ?? '-'
    );
}

echo PHP_EOL.'=== Firewall filter rules ==='.PHP_EOL;

foreach ($client->query('/ip/firewall/filter/print')->read() as $row) {
    echo sprintf(
        "chain=%-10s action=%-10s src=%-16s dst=%-16s dynamic=%s comment=%s\n",
        $row['chain'] ?? '-',
        $row['action'] ?? '-',
        $row['src-address'] ?? '-',
        $row['dst-address'] ?? '-',
        $row['dynamic'] ?? '-',
        $row['comment'] ?? ''
    );
}

echo PHP_EOL;
