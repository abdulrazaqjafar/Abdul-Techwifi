<?php

namespace App\Http\Controllers;

use App\Models\HotspotTransaction;
use App\Services\HotspotPurchaseService;
use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClickPesaWebhookController extends Controller
{
    public function __construct(
        protected ClickPesaClient $client,
        protected HotspotPurchaseService $purchaseService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        if ($payload === []) {
            return response()->json(['message' => 'Empty payload.'], 400);
        }

        $checksum = $payload['checksum'] ?? $request->header('checksum');

        if (is_string($checksum) && $checksum !== '' && ! $this->client->verifyPayloadChecksum($payload, $checksum)) {
            Log::warning('ClickPesa webhook checksum verification failed.', [
                'order_reference' => data_get($payload, 'data.orderReference'),
            ]);

            return response()->json(['message' => 'Invalid checksum.'], 401);
        }

        $event = strtoupper((string) ($payload['event'] ?? $payload['eventType'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $orderReference = (string) ($data['orderReference'] ?? '');

        if ($orderReference === '') {
            return response()->json(['message' => 'Missing order reference.'], 422);
        }

        $transaction = HotspotTransaction::query()
            ->where('reference', $orderReference)
            ->orWhere('gateway_reference', $orderReference)
            ->first();

        if (! $transaction) {
            $sanitized = $this->client->sanitizeOrderReference($orderReference);

            $transaction = HotspotTransaction::query()
                ->whereRaw("REPLACE(reference, '-', '') = ?", [$sanitized])
                ->first();
        }

        if (! $transaction) {
            Log::warning('ClickPesa webhook received for unknown transaction.', [
                'order_reference' => $orderReference,
                'event' => $event,
            ]);

            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        $status = strtoupper((string) ($data['status'] ?? ''));

        if ($this->isSuccessEvent($event, $status)) {
            $this->purchaseService->processPaymentNotification($transaction->reference, 'SUCCESS');

            return response()->json(['message' => 'Payment processed.']);
        }

        if ($this->isFailureEvent($event, $status)) {
            $this->purchaseService->processPaymentNotification(
                $transaction->reference,
                'FAILED',
                (string) ($data['message'] ?? 'Payment failed.')
            );

            return response()->json(['message' => 'Payment failure recorded.']);
        }

        return response()->json(['message' => 'Event acknowledged.']);
    }

    protected function isSuccessEvent(string $event, string $status): bool
    {
        return str_contains($event, 'RECEIVED')
            || str_contains($event, 'SUCCESS')
            || in_array($status, ['SUCCESS', 'SETTLED'], true);
    }

    protected function isFailureEvent(string $event, string $status): bool
    {
        return str_contains($event, 'FAILED')
            || in_array($status, ['FAILED', 'REVERSED', 'REFUNDED'], true);
    }
}
