<?php

/**
 * Show what is actually stored in every table before anything is deleted, so
 * the reset can be scoped precisely instead of guessed at.
 *
 * Run: php scripts/inspect-data.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$database = DB::getDatabaseName();

$tables = collect(DB::select('SHOW TABLES'))
    ->map(fn ($row) => array_values((array) $row)[0])
    ->sort()
    ->values();

echo '=== Jedwali zote katika '.$database.' ==='.PHP_EOL;

foreach ($tables as $table) {
    $count = DB::table($table)->count();

    echo sprintf("  %-34s %6d\n", $table, $count);
}

echo PHP_EOL.'=== Vocha kwa chanzo na hali ==='.PHP_EOL;

$vouchers = DB::table('vouchers')
    ->select('source', 'status', DB::raw('COUNT(*) as jumla'))
    ->groupBy('source', 'status')
    ->get();

foreach ($vouchers as $row) {
    echo sprintf("  %-12s %-12s %d\n", $row->source ?? '-', $row->status, $row->jumla);
}

echo PHP_EOL.'=== Mapato yaliyorekodiwa ==='.PHP_EOL;

foreach (['sales' => 'amount', 'payments' => 'amount'] as $table => $column) {
    if (! $tables->contains($table)) {
        continue;
    }

    $total = DB::table($table)->sum($column);
    $count = DB::table($table)->count();

    echo sprintf("  %-10s rekodi=%-5d jumla=TSh %s\n", $table, $count, number_format((float) $total));
}

echo PHP_EOL.'=== Vitu vya kubaki (mipangilio) ==='.PHP_EOL;

foreach (['packages', 'admins', 'business_settings', 'users', 'roles', 'permissions'] as $table) {
    if (! $tables->contains($table)) {
        continue;
    }

    echo sprintf("  %-20s %d\n", $table, DB::table($table)->count());
}

echo PHP_EOL.'=== Vifurushi vilivyopo ==='.PHP_EOL;

foreach (DB::table('packages')->orderBy('id')->get(['id', 'name', 'price', 'is_active']) as $package) {
    echo sprintf(
        "  #%-3s %-24s TSh %-9s %s\n",
        $package->id,
        $package->name,
        number_format((float) $package->price),
        $package->is_active ? 'hai' : 'imezimwa'
    );
}

echo PHP_EOL;
