<?php

/**
 * Voucher deadlines live on the router's clock, and this board keeps no time
 * across a power cut, so NTP has to repair it automatically.
 *
 * RouterOS 6 silently ignores a whole `set` when one property is unknown to
 * it, so each property is applied on its own and read back.
 *
 * Run: php scripts/fix-router-clock.php
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
    'timeout' => 20,
    'attempts' => 2,
]);

$read = static function () use ($client): array {
    return $client->query('/system/ntp/client/print')->read()[0] ?? [];
};

$apply = static function (string $field, string $value) use ($client, $read): void {
    try {
        $client->query(
            (new RouterOS\Query('/system/ntp/client/set'))->equal($field, $value)
        )->read();
    } catch (Throwable $exception) {
        echo sprintf("  %-18s KOSA: %s\n", $field, trim($exception->getMessage()));

        return;
    }

    $now = $read();
    $stored = $now[$field] ?? '(haipo)';

    echo sprintf(
        "  %-18s %s  %s\n",
        $field,
        $stored,
        $stored === $value ? '' : '<< haikukubaliwa'
    );
};

echo '=== Hali ya awali ==='.PHP_EOL;

foreach ($read() as $key => $value) {
    echo sprintf("  %-18s %s\n", $key, $value);
}

echo PHP_EOL.'=== Kuweka kila kipengele peke yake ==='.PHP_EOL;

$apply('mode', 'unicast');
$apply('primary-ntp', '216.239.35.0');
$apply('secondary-ntp', '162.159.200.1');
$apply('enabled', 'yes');

echo PHP_EOL.'=== Kusubiri irekebishe saa ==='.PHP_EOL;

sleep(10);

foreach ($read() as $key => $value) {
    echo sprintf("  %-18s %s\n", $key, $value);
}

$clock = $client->query('/system/clock/print')->read()[0] ?? [];

echo PHP_EOL.'  saa ya router      '.($clock['date'] ?? '-').' '.($clock['time'] ?? '-').PHP_EOL;
echo '  saa ya seva        '.date('M/d/Y H:i:s').PHP_EOL;

echo PHP_EOL.(($read()['enabled'] ?? 'false') === 'true'
    ? 'NTP imewashwa — saa itajirekebisha umeme ukirudi.'
    : 'NTP HAIJAWASHWA — inabidi tuiwashe kwa Winbox.').PHP_EOL.PHP_EOL;
