<?php

/**
 * Sessions were dying with "logged out: keepalive timeout" a few minutes after
 * the customer stopped touching the phone, which forces them to enter the
 * voucher again even though they paid for a day. A paid session should only end
 * when its uptime limit runs out, so drop the keepalive and idle timeouts.
 *
 * Run: php scripts/fix-session-stability.php
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

foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $row) {
    if (! isset($row['.id'])) {
        continue;
    }

    try {
        $client->query(
            (new RouterOS\Query('/ip/hotspot/user/profile/set'))
                ->equal('.id', $row['.id'])
                ->equal('keepalive-timeout', 'none')
                ->equal('idle-timeout', 'none')
        )->read();

        echo 'Profile imesahihishwa: '.($row['name'] ?? '-').PHP_EOL;
    } catch (Throwable $exception) {
        echo 'IMESHINDIKANA ('.($row['name'] ?? '-').'): '.trim($exception->getMessage()).PHP_EOL;
    }
}

foreach ($client->query('/ip/hotspot/profile/print')->read() as $row) {
    if (! isset($row['.id'])) {
        continue;
    }

    try {
        $client->query(
            (new RouterOS\Query('/ip/hotspot/profile/set'))
                ->equal('.id', $row['.id'])
                ->equal('login-by', 'http-chap,http-pap')
        )->read();

        echo 'Server profile imesahihishwa: '.($row['name'] ?? '-').PHP_EOL;
    } catch (Throwable $exception) {
        echo 'IMESHINDIKANA ('.($row['name'] ?? '-').'): '.trim($exception->getMessage()).PHP_EOL;
    }
}

foreach ($client->query('/ip/hotspot/print')->read() as $row) {
    if (! isset($row['.id'])) {
        continue;
    }

    try {
        $client->query(
            (new RouterOS\Query('/ip/hotspot/set'))
                ->equal('.id', $row['.id'])
                ->equal('idle-timeout', 'none')
                ->equal('keepalive-timeout', 'none')
        )->read();

        echo 'Hotspot server imesahihishwa: '.($row['name'] ?? '-').PHP_EOL;
    } catch (Throwable $exception) {
        echo 'IMESHINDIKANA ('.($row['name'] ?? '-').'): '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo PHP_EOL.'=== Hali ya sasa ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $row) {
    echo sprintf(
        "  profile=%-24s keepalive=%-8s idle=%-8s rate-limit=%s\n",
        $row['name'] ?? '-',
        $row['keepalive-timeout'] ?? '-',
        $row['idle-timeout'] ?? '-',
        $row['rate-limit'] ?? '-'
    );
}

foreach ($client->query('/ip/hotspot/print')->read() as $row) {
    echo sprintf(
        "  server=%-14s idle=%-8s keepalive=%s\n",
        $row['name'] ?? '-',
        $row['idle-timeout'] ?? '-',
        $row['keepalive-timeout'] ?? '-'
    );
}

echo PHP_EOL;
