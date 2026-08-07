<?php

/**
 * Show records created since the reset, to confirm the fresh system is
 * recording real activity from record number 1.
 *
 * Run: php scripts/check-new-activity.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo '=== Saa na lugha ==='.PHP_EOL;
echo '  saa ya mfumo   : '.now()->format('Y-m-d H:i:s').PHP_EOL;
echo '  eneo la saa    : '.config('app.timezone').PHP_EOL;
echo '  lugha ya awali : '.config('app.locale').PHP_EOL;
echo '  debug          : '.var_export(config('app.debug'), true).PHP_EOL;

echo PHP_EOL.'=== Vocha zilizotengenezwa ==='.PHP_EOL;

foreach (DB::table('vouchers')->orderBy('id')->get() as $voucher) {
    echo sprintf(
        "  #%-3s %-14s %-9s chanzo=%-8s imetengenezwa=%s\n",
        $voucher->id,
        $voucher->code,
        $voucher->status,
        $voucher->source ?? '-',
        $voucher->created_at ?? '-'
    );
}

echo PHP_EOL.'=== Shughuli za hivi karibuni ==='.PHP_EOL;

foreach (DB::table('activity_logs')->orderByDesc('id')->limit(10)->get() as $log) {
    echo sprintf(
        "  #%-3s %-19s %s\n",
        $log->id,
        $log->created_at ?? '-',
        $log->description ?? ($log->action ?? '-')
    );
}

echo PHP_EOL;
