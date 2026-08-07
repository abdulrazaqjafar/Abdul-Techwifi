<?php

/**
 * Ask ClickPesa itself which payment methods are live on this merchant account,
 * using the real numbers that failed. Preview only reads; it never charges.
 *
 * Also re-checks the transactions that were left hanging, in case somebody paid
 * and the system never noticed.
 *
 * Run: php scripts/test-clickpesa-methods.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Support\Facades\DB;

$client = app(ClickPesaClient::class);

echo '=== Muunganisho na ClickPesa ==='.PHP_EOL;

try {
    $result = $client->testConnection();
    echo '  OK  mazingira='.$result['environment'].'  url='.$result['base_url'].PHP_EOL;
} catch (Throwable $exception) {
    echo '  IMESHINDIKANA: '.trim($exception->getMessage()).PHP_EOL;

    exit(1);
}

$samples = DB::table('hotspot_transactions')
    ->whereNotNull('phone')
    ->orderByDesc('id')
    ->get(['phone'])
    ->pluck('phone')
    ->map(static fn ($phone) => preg_replace('/\D/', '', (string) $phone))
    ->filter()
    ->unique()
    ->values();

echo PHP_EOL.'=== Njia zilizo hai kwa kila namba ==='.PHP_EOL;

foreach ($samples->take(6) as $phone) {
    $normalized = str_starts_with($phone, '255') ? $phone : '255'.ltrim($phone, '0');

    echo PHP_EOL.'  Namba: '.$normalized.PHP_EOL;

    try {
        $preview = $client->previewUssdPush([
            'amount' => '1000',
            'currency' => 'TZS',
            'orderReference' => 'PREVIEW'.now()->format('YmdHis').random_int(100, 999),
            'phoneNumber' => $normalized,
        ]);

        $methods = $preview['activeMethods'] ?? $preview['methods'] ?? [];

        if ($methods === []) {
            echo '    (hakuna njia iliyorudishwa) '.json_encode($preview).PHP_EOL;
        }

        foreach ($methods as $method) {
            echo sprintf(
                "    %-16s status=%-14s %s\n",
                $method['name'] ?? '-',
                $method['status'] ?? '-',
                $method['message'] ?? ''
            );
        }
    } catch (Throwable $exception) {
        echo '    KOSA: '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo PHP_EOL.'=== Miamala iliyokwama — hali halisi ClickPesa ==='.PHP_EOL;

$stuck = DB::table('hotspot_transactions')
    ->whereIn('status', ['processing', 'pending'])
    ->whereNotNull('gateway_reference')
    ->orderByDesc('id')
    ->get(['id', 'reference', 'gateway_reference', 'phone', 'amount', 'status', 'created_at']);

if ($stuck->isEmpty()) {
    echo '  (hakuna iliyofika ClickPesa)'.PHP_EOL;
}

foreach ($stuck as $row) {
    echo sprintf("  #%-4s %s  kiasi=%s  ref=%s\n", $row->id, $row->created_at, $row->amount, $row->reference);

    try {
        $status = $client->queryPaymentStatus($client->sanitizeOrderReference((string) $row->reference));

        if ($status === []) {
            echo '        ClickPesa: haipo (haikupokelewa)'.PHP_EOL;

            continue;
        }

        foreach ($status as $entry) {
            if (! is_array($entry)) {
                echo '        '.json_encode($status).PHP_EOL;

                break;
            }

            echo sprintf(
                "        ClickPesa: status=%-12s kiasi=%-9s njia=%s\n",
                $entry['status'] ?? '-',
                $entry['collectedAmount'] ?? ($entry['amount'] ?? '-'),
                $entry['channel'] ?? ($entry['paymentMethod'] ?? '-')
            );
        }
    } catch (Throwable $exception) {
        echo '        KOSA: '.trim($exception->getMessage()).PHP_EOL;
    }
}

echo PHP_EOL;
