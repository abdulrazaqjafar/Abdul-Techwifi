<?php

/**
 * Group the failed payments by mobile network. If one network fails while the
 * others succeed, the problem is the payment method's activation on the
 * merchant account rather than anything in this system.
 *
 * Run: php scripts/diagnose-payments-detail.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$network = static function (?string $phone): string {
    $digits = preg_replace('/\D/', '', (string) $phone) ?? '';
    $local = str_starts_with($digits, '255') ? '0'.substr($digits, 3) : $digits;
    $prefix = substr($local, 0, 3);

    return match (true) {
        in_array($prefix, ['074', '075', '076'], true) => 'Vodacom (M-Pesa)',
        in_array($prefix, ['068', '069', '078'], true) => 'Airtel (Airtel Money)',
        in_array($prefix, ['071', '065', '067', '077'], true) => 'Tigo/Yas (Mixx)',
        in_array($prefix, ['062', '073'], true) => 'Halotel (Halopesa)',
        default => $local === '' ? 'haijulikani' : 'nyingine ('.$prefix.')',
    };
};

echo '=== Miamala yote kwa mtandao na hali ==='.PHP_EOL;

$rows = DB::table('hotspot_transactions')
    ->orderByDesc('id')
    ->get(['id', 'phone', 'amount', 'status', 'failure_reason', 'gateway_reference', 'created_at']);

$summary = [];

foreach ($rows as $row) {
    $key = $network($row->phone).' | '.$row->status;
    $summary[$key] = ($summary[$key] ?? 0) + 1;
}

ksort($summary);

foreach ($summary as $key => $count) {
    echo sprintf("  %-40s %d\n", $key, $count);
}

echo PHP_EOL.'=== Miamala 25 ya mwisho kwa undani ==='.PHP_EOL;

foreach ($rows->take(25) as $row) {
    echo sprintf(
        "  #%-4s %-19s %-11s %-24s kiasi=%-9s ref=%s\n",
        $row->id,
        $row->created_at,
        $row->status,
        $network($row->phone),
        $row->amount,
        $row->gateway_reference ?: '-'
    );

    if (! empty($row->failure_reason)) {
        echo '        sababu: '.$row->failure_reason.PHP_EOL;
    }
}

echo PHP_EOL.'=== Miamala iliyokamilika ==='.PHP_EOL;

foreach ($rows->where('status', 'completed') as $row) {
    echo sprintf(
        "  #%-4s %-19s %-24s kiasi=%s\n",
        $row->id,
        $row->created_at,
        $network($row->phone),
        $row->amount
    );
}

echo PHP_EOL;
