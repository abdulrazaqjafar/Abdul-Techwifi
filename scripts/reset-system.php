<?php

/**
 * Clear the trial data so the business can start on real records.
 *
 * Wipes transactions, vouchers, sales, payments, customers and logs, and
 * removes the voucher users those records created on the router. Packages,
 * staff accounts, business settings and router settings are left untouched.
 *
 * Counters restart at 1, so the first real sale is sale #1.
 *
 * Run: php scripts/reset-system.php --force
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

if (! in_array('--force', $argv ?? [], true)) {
    echo 'Hii inafuta data ya majaribio na haiwezi kutenduliwa bila nakala ya usalama.'.PHP_EOL;
    echo 'Ukiwa tayari, endesha: php scripts/reset-system.php --force'.PHP_EOL;

    exit(1);
}

/** Trial records. Emptied and their counters restarted. */
$wipe = [
    'hotspot_transactions',
    'vouchers',
    'sales',
    'payments',
    'customer_service_snapshots',
    'customer_offers',
    'customers',
    'activity_logs',
    'sms_logs',
    'sessions',
    'cache',
    'cache_locks',
    'jobs',
    'job_batches',
    'failed_jobs',
];

/** Configuration. Never touched by this script. */
$keep = [
    'packages',
    'admins',
    'users',
    'business_settings',
    'router_settings',
    'migrations',
];

echo '=== Kabla ya kuanzisha upya ==='.PHP_EOL;

foreach ($wipe as $table) {
    echo sprintf("  %-30s %d\n", $table, DB::table($table)->count());
}

DB::statement('SET FOREIGN_KEY_CHECKS=0');

foreach ($wipe as $table) {
    DB::table($table)->truncate();
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo PHP_EOL.'=== Baada ya kuanzisha upya ==='.PHP_EOL;

foreach ($wipe as $table) {
    echo sprintf("  %-30s %d\n", $table, DB::table($table)->count());
}

echo PHP_EOL.'=== Vilivyobaki salama ==='.PHP_EOL;

foreach ($keep as $table) {
    echo sprintf("  %-30s %d\n", $table, DB::table($table)->count());
}

echo PHP_EOL.'=== Router: kuondoa vocha za majaribio ==='.PHP_EOL;

$settings = App\Models\RouterSetting::resolve();

try {
    $client = new RouterOS\Client([
        'host' => $settings->router_ip,
        'port' => $settings->router_port ?: 8728,
        'user' => $settings->router_username,
        'pass' => $settings->router_password,
        'timeout' => 10,
        'attempts' => 2,
    ]);
} catch (Throwable $exception) {
    echo '  Router haikufikika: '.trim($exception->getMessage()).PHP_EOL;
    echo '  Database imesafishwa. Endesha tena router ikirudi ili kusafisha upande wake.'.PHP_EOL;

    exit(0);
}

$removeAll = function (string $path, callable $shouldRemove, string $label) use ($client): void {
    try {
        $rows = $client->query($path.'/print')->read();
    } catch (Throwable $exception) {
        echo '  '.$label.': '.trim($exception->getMessage()).PHP_EOL;

        return;
    }

    $removed = 0;

    foreach ($rows as $row) {
        if (! isset($row['.id']) || ! $shouldRemove($row)) {
            continue;
        }

        try {
            $client->query((new RouterOS\Query($path.'/remove'))->equal('.id', $row['.id']))->read();
            $removed++;
        } catch (Throwable $exception) {
            echo '    imeshindikana: '.trim($exception->getMessage()).PHP_EOL;
        }
    }

    echo sprintf("  %-28s zimeondolewa %d kati ya %d\n", $label, $removed, count($rows));
};

// Only the accounts this system generated. Anything set up by hand on the
// router stays.
$removeAll(
    '/ip/hotspot/user',
    fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'voucher_'),
    'watumiaji wa vocha'
);

$removeAll('/ip/hotspot/active', fn (): bool => true, 'vipindi vinavyoendelea');
$removeAll('/ip/hotspot/cookie', fn (): bool => true, 'cookies za kuingia');
$removeAll('/ip/hotspot/host', fn (): bool => true, 'kumbukumbu za vifaa');

echo PHP_EOL.'=== Router: kilichobaki ==='.PHP_EOL;

foreach ([
    '/ip/hotspot/user' => 'watumiaji wa hotspot',
    '/ip/hotspot/user/profile' => 'profiles za vifurushi',
    '/ip/hotspot/ip-binding' => 'ip-binding (seva ya malipo)',
] as $path => $label) {
    try {
        echo sprintf("  %-30s %d\n", $label, count($client->query($path.'/print')->read()));
    } catch (Throwable $exception) {
        echo '  '.$label.': '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo PHP_EOL.'Mfumo uko tayari kwa matumizi rasmi.'.PHP_EOL.PHP_EOL;
