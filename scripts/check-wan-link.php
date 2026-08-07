<?php

/**
 * The router reaches the internet far worse than a PC on the same upstream
 * network. Inspect the physical WAN link: negotiated speed, duplex and error
 * counters, since a bad cable or duplex mismatch produces exactly that.
 *
 * Run: php scripts/check-wan-link.php
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

echo '=== Ethernet ports ==='.PHP_EOL;

foreach ($client->query('/interface/ethernet/print')->read() as $row) {
    echo sprintf(
        "  %-8s speed=%-10s auto-neg=%-6s running=%-6s disabled=%s\n",
        $row['name'] ?? '-',
        $row['speed'] ?? '-',
        $row['auto-negotiation'] ?? '-',
        $row['running'] ?? '-',
        $row['disabled'] ?? '-'
    );
}

echo PHP_EOL.'=== ether1 monitor ==='.PHP_EOL;

try {
    foreach ($client->query(
        (new RouterOS\Query('/interface/ethernet/monitor'))
            ->equal('numbers', 'ether1')
            ->equal('once', '')
    )->read() as $row) {
        foreach ($row as $key => $value) {
            if (str_starts_with((string) $key, '.')) {
                continue;
            }

            echo sprintf("  %-24s %s\n", $key, $value);
        }
    }
} catch (Throwable $exception) {
    echo '  ERROR: '.trim($exception->getMessage()).PHP_EOL;
}

echo PHP_EOL.'=== Makosa kwenye ether1 ==='.PHP_EOL;

try {
    foreach ($client->query(
        (new RouterOS\Query('/interface/ethernet/print'))
            ->equal('stats', '')
            ->equal('.proplist', 'name,rx-error,tx-error,rx-drop,tx-drop,rx-fcs-error,tx-collision,tx-excessive-collision,rx-overflow,tx-underrun')
    )->read() as $row) {
        if (($row['name'] ?? '') !== 'ether1') {
            continue;
        }

        foreach ($row as $key => $value) {
            if (str_starts_with((string) $key, '.') || $key === 'name') {
                continue;
            }

            echo sprintf("  %-24s %s\n", $key, $value);
        }
    }
} catch (Throwable $exception) {
    echo '  ERROR: '.trim($exception->getMessage()).PHP_EOL;
}

echo PHP_EOL.'=== Ping kutoka router ==='.PHP_EOL;

foreach (['192.168.1.1', '8.8.8.8'] as $target) {
    try {
        $rows = $client->query(
            (new RouterOS\Query('/ping'))
                ->equal('address', $target)
                ->equal('count', '5')
        )->read();

        $last = end($rows) ?: [];

        echo sprintf(
            "  %-14s sent=%-4s received=%-4s loss=%-6s avg=%s\n",
            $target,
            $last['sent'] ?? '-',
            $last['received'] ?? '-',
            ($last['packet-loss'] ?? '-').'%',
            $last['avg-rtt'] ?? '-'
        );
    } catch (Throwable $exception) {
        echo '  '.$target.' ERROR: '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo PHP_EOL;
