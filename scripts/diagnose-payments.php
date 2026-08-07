<?php

/**
 * Payments succeed sometimes and fail other times. Look at the actual record:
 * which transactions completed, which died, how long they took, and what the
 * gateway said when they failed.
 *
 * Run: php scripts/diagnose-payments.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo '=== Miamala kwa hali ==='.PHP_EOL;

foreach (DB::table('hotspot_transactions')->select('status', DB::raw('COUNT(*) as jumla'))->groupBy('status')->get() as $row) {
    echo sprintf("  %-16s %d\n", $row->status, $row->jumla);
}

echo PHP_EOL.'=== Miamala 20 ya mwisho ==='.PHP_EOL;

$columns = DB::getSchemaBuilder()->getColumnListing('hotspot_transactions');

foreach (DB::table('hotspot_transactions')->orderByDesc('id')->limit(20)->get() as $row) {
    $created = $row->created_at ?? '-';
    $updated = $row->updated_at ?? '-';

    $seconds = ($created !== '-' && $updated !== '-')
        ? strtotime((string) $updated) - strtotime((string) $created)
        : null;

    echo sprintf(
        "  #%-4s %-19s %-12s muda=%-7s simu=%-14s kiasi=%-8s\n",
        $row->id,
        $created,
        $row->status ?? '-',
        $seconds === null ? '-' : $seconds.'s',
        $row->phone_number ?? ($row->msisdn ?? '-'),
        $row->amount ?? '-'
    );

    foreach (['failure_reason', 'error_message', 'message', 'gateway_message'] as $field) {
        if (in_array($field, $columns, true) && ! empty($row->{$field})) {
            echo '        sababu: '.$row->{$field}.PHP_EOL;
        }
    }
}

echo PHP_EOL.'=== Safu zilizopo kwenye hotspot_transactions ==='.PHP_EOL;
echo '  '.implode(', ', $columns).PHP_EOL;

echo PHP_EOL.'=== Makosa ya ClickPesa kwenye log ==='.PHP_EOL;

$logFile = __DIR__.'/../storage/logs/laravel.log';

if (! is_file($logFile)) {
    echo '  (hakuna log)'.PHP_EOL;
} else {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
    $matches = [];

    foreach ($lines as $line) {
        $haystack = strtolower($line);

        $isRelevant = str_contains($haystack, 'clickpesa')
            || str_contains($haystack, 'payment')
            || str_contains($haystack, 'curl')
            || str_contains($haystack, 'timed out')
            || str_contains($haystack, 'connection');

        if ($isRelevant) {
            $matches[] = $line;
        }
    }

    echo '  Mistari inayohusika: '.count($matches).PHP_EOL.PHP_EOL;

    foreach (array_slice($matches, -30) as $line) {
        echo '  '.mb_substr($line, 0, 220).PHP_EOL;
    }
}

echo PHP_EOL.'=== Mipangilio ya sasa ==='.PHP_EOL;

echo sprintf("  provider          %s\n", config('hotspot.payment.default_provider'));
echo sprintf("  timeout           %s\n", config('hotspot.payment.timeout'));
echo sprintf("  sandbox           %s\n", var_export(config('hotspot.providers.clickpesa.sandbox'), true));
echo sprintf("  min_amount        %s\n", config('hotspot.providers.clickpesa.min_amount'));
echo sprintf("  max_amount        %s\n", config('hotspot.providers.clickpesa.max_amount'));
echo sprintf("  checksum_enabled  %s\n", var_export(config('hotspot.providers.clickpesa.checksum_enabled'), true));
echo sprintf("  checksum_key      %s\n", config('hotspot.providers.clickpesa.checksum_key') ? 'imewekwa' : 'HAIJAWEKWA');

echo PHP_EOL;
