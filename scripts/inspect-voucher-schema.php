<?php

/**
 * Confirm the voucher columns and the profile names packages expect, so the
 * router provisioning writes the right values.
 *
 * Run: php scripts/inspect-voucher-schema.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo '=== Safu za vouchers ==='.PHP_EOL;

foreach (DB::getSchemaBuilder()->getColumnListing('vouchers') as $column) {
    echo '  '.$column.PHP_EOL;
}

echo PHP_EOL.'=== Vifurushi na profile zake ==='.PHP_EOL;

foreach (DB::table('packages')->orderBy('id')->get() as $package) {
    echo sprintf(
        "  #%-3s %-24s profile=%-22s siku=%-4s saa=%-4s %s\n",
        $package->id,
        $package->name,
        $package->profile_name ?? '(hakuna)',
        $package->duration_days,
        $package->duration_hours,
        $package->is_active ? 'hai' : 'imezimwa'
    );
}

echo PHP_EOL.'=== Profile zilizopo kwenye router ==='.PHP_EOL;

try {
    $settings = App\Models\RouterSetting::resolve();

    $client = new RouterOS\Client([
        'host' => $settings->router_ip,
        'port' => $settings->router_port ?: 8728,
        'user' => $settings->router_username,
        'pass' => $settings->router_password,
        'timeout' => 10,
        'attempts' => 2,
    ]);

    foreach ($client->query('/ip/hotspot/user/profile/print')->read() as $profile) {
        echo sprintf(
            "  %-24s rate-limit=%s\n",
            $profile['name'] ?? '-',
            $profile['rate-limit'] ?? '-'
        );
    }
} catch (Throwable $exception) {
    echo '  Router haikufikika: '.trim($exception->getMessage()).PHP_EOL;
}

echo PHP_EOL;
