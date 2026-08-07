<?php

/**
 * Housekeeping for payments.
 *
 * Attempts ClickPesa never accepted (no gateway reference) are closed off as
 * expired so the reports stop counting them as live. Attempts ClickPesa DID
 * accept are checked against the gateway first, because one of them may be a
 * customer who paid and never received a voucher.
 *
 * Run: php scripts/reconcile-payments.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HotspotPurchaseService;
use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Support\Facades\DB;

$openStatuses = ['pending', 'processing'];

echo '=== Majaribio ambayo hayakufika ClickPesa ==='.PHP_EOL;

$abandoned = DB::table('hotspot_transactions')
    ->whereIn('status', $openStatuses)
    ->whereNull('gateway_reference')
    ->where('expires_at', '<', now())
    ->get(['id', 'phone', 'created_at']);

foreach ($abandoned as $row) {
    DB::table('hotspot_transactions')
        ->where('id', $row->id)
        ->update([
            'status' => 'expired',
            'failure_reason' => 'Ombi halikupokelewa na ClickPesa.',
            'updated_at' => now(),
        ]);

    echo sprintf("  #%-4s %s  %s  imewekwa: expired\n", $row->id, $row->created_at, $row->phone);
}

echo '  Jumla: '.$abandoned->count().PHP_EOL;

echo PHP_EOL.'=== Miamala iliyofika ClickPesa — inahakikiwa ==='.PHP_EOL;

$pendingAtGateway = DB::table('hotspot_transactions')
    ->whereIn('status', $openStatuses)
    ->whereNotNull('gateway_reference')
    ->orderBy('id')
    ->get(['id', 'reference', 'phone', 'amount', 'created_at']);

if ($pendingAtGateway->isEmpty()) {
    echo '  (hakuna)'.PHP_EOL;
}

$client = app(ClickPesaClient::class);
$purchaseService = app(HotspotPurchaseService::class);

foreach ($pendingAtGateway as $row) {
    echo sprintf("  #%-4s %s  %s  kiasi=%s\n", $row->id, $row->created_at, $row->phone, $row->amount);

    try {
        $payments = $client->queryPaymentStatus($client->sanitizeOrderReference((string) $row->reference));

        if ($payments === []) {
            echo '        ClickPesa haina kumbukumbu ya malipo haya.'.PHP_EOL;

            continue;
        }

        foreach ($payments as $payment) {
            $status = strtoupper((string) ($payment['status'] ?? '-'));

            echo sprintf(
                "        ClickPesa: %s  kiasi=%s\n",
                $status,
                $payment['collectedAmount'] ?? ($payment['amount'] ?? '-')
            );

            if (in_array($status, ['SUCCESS', 'SETTLED'], true)) {
                $purchaseService->processPaymentNotification((string) $row->reference, $status);

                echo '        >>> MTEJA ALILIPA. Voucher imetengenezwa sasa.'.PHP_EOL;
            }
        }
    } catch (Throwable $exception) {
        echo '        Haikuhakikiwa: '.trim($exception->getMessage()).PHP_EOL;
        echo '        (jaribu tena kesho kikomo cha maombi kikirudi)'.PHP_EOL;
    }
}

echo PHP_EOL.'=== Hali baada ya usafi ==='.PHP_EOL;

$summary = DB::table('hotspot_transactions')
    ->select('status', DB::raw('COUNT(*) as jumla'))
    ->groupBy('status')
    ->get();

foreach ($summary as $row) {
    echo sprintf("  %-12s %d\n", $row->status, $row->jumla);
}

echo PHP_EOL;
