<?php

/**
 * Before going live, make sure nobody can reach the internet without paying:
 * no trial logins, no leftover MAC cookies, and nothing bypassing the hotspot.
 *
 * Run: php scripts/check-free-access.php
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
    'timeout' => 10,
    'attempts' => 2,
]);

echo '=== Profile ya seva ya hotspot ==='.PHP_EOL;

$leak = false;

foreach ($client->query('/ip/hotspot/profile/print')->read() as $profile) {
    $loginBy = (string) ($profile['login-by'] ?? '');

    echo sprintf("  %-16s login-by=%s\n", $profile['name'] ?? '-', $loginBy ?: '-');

    if (str_contains($loginBy, 'trial')) {
        echo '    >>> ONYO: trial imewashwa — watu wanaweza kupata intaneti bure.'.PHP_EOL;
        $leak = true;
    }

    if (str_contains($loginBy, 'mac') && ! str_contains($loginBy, 'mac-cookie')) {
        echo '    >>> ONYO: login kwa MAC pekee imewashwa — hairuhusiwi kwa biashara.'.PHP_EOL;
        $leak = true;
    }
}

echo PHP_EOL.'=== Watumiaji walio na ruhusa ya kudumu ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/user/print')->read() as $user) {
    echo sprintf(
        "  %-20s profile=%-16s limit-uptime=%s\n",
        $user['name'] ?? '-',
        $user['profile'] ?? '-',
        $user['limit-uptime'] ?? 'hakuna'
    );
}

echo PHP_EOL.'=== IP binding (wanaopita bila kulipa) ==='.PHP_EOL;

foreach ($client->query('/ip/hotspot/ip-binding/print')->read() as $binding) {
    $type = (string) ($binding['type'] ?? '-');

    echo sprintf(
        "  %-16s %-18s type=%s  %s\n",
        $binding['address'] ?? '-',
        $binding['mac-address'] ?? '-',
        $type,
        $binding['comment'] ?? ''
    );

    if ($type === 'bypassed' && ($binding['address'] ?? '') !== '192.168.20.253') {
        echo '    >>> ANGALIA: kifaa hiki kinapita bila kulipa.'.PHP_EOL;
        $leak = true;
    }
}

echo PHP_EOL.($leak
    ? 'Kuna njia ya kupata intaneti bure. Angalia maonyo hapo juu.'
    : 'Hakuna njia ya bure. Kila mtu lazima alipe au atumie vocha.').PHP_EOL.PHP_EOL;
