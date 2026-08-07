<?php

/**
 * Every voucher deadline is anchored to the router's clock, and the hAP ac
 * lite has no battery to keep time across a power cut. Without NTP the clock
 * would come back wrong and timers would fire early or never.
 *
 * Run: php scripts/check-router-clock.php
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

echo '=== Saa ya router ==='.PHP_EOL;

$clock = $client->query('/system/clock/print')->read()[0] ?? [];

foreach (['date', 'time', 'time-zone-name', 'time-zone-autodetect', 'gmt-offset'] as $field) {
    if (array_key_exists($field, $clock)) {
        echo sprintf("  %-22s %s\n", $field, $clock[$field]);
    }
}

$routerTime = strtotime(($clock['date'] ?? '').' '.($clock['time'] ?? ''));
$drift = $routerTime ? abs(time() - $routerTime) : null;

echo '  saa ya seva            '.date('M/d/Y H:i:s').PHP_EOL;
echo '  tofauti                '.($drift === null ? 'haijulikani' : $drift.' sekunde').PHP_EOL;

echo PHP_EOL.'=== NTP (kurekebisha saa kwa mtandao) ==='.PHP_EOL;

$ntp = [];

foreach (['/system/ntp/client/print'] as $path) {
    try {
        $ntp = $client->query($path)->read()[0] ?? [];
    } catch (Throwable $exception) {
        echo '  '.trim($exception->getMessage()).PHP_EOL;
    }
}

foreach (['enabled', 'mode', 'primary-ntp', 'secondary-ntp', 'server-dns-names', 'status', 'last-update-from'] as $field) {
    if (array_key_exists($field, $ntp)) {
        echo sprintf("  %-22s %s\n", $field, $ntp[$field]);
    }
}

$ntpOn = ($ntp['enabled'] ?? 'false') === 'true';

echo PHP_EOL.'=== Hitimisho ==='.PHP_EOL;

if (! $ntpOn) {
    echo '  ONYO: NTP haijawashwa. Umeme ukikatika, saa ya router itapotea'.PHP_EOL;
    echo '  na muda wa vocha hautahesabiwa sawa.'.PHP_EOL;
} elseif ($drift !== null && $drift > 120) {
    echo '  ONYO: saa ya router imepishana na seva kwa '.$drift.' sekunde.'.PHP_EOL;
} else {
    echo '  Saa ipo sawa na inarekebishwa yenyewe kwa mtandao.'.PHP_EOL;
}

echo PHP_EOL;
