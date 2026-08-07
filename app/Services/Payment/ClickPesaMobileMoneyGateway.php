<?php

namespace App\Services\Payment;

use App\Contracts\MobileMoneyGatewayInterface;
use App\Models\HotspotTransaction;
use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ClickPesaMobileMoneyGateway implements MobileMoneyGatewayInterface
{
    /**
     * The browser asks for progress every few seconds so the customer sees a
     * live status, but ClickPesa does not need to be asked that often. Holding
     * the last answer briefly keeps the screen responsive while cutting the
     * number of API calls per payment by roughly four fifths.
     */
    protected const STATUS_CACHE_SECONDS = 10;

    public function __construct(
        protected ClickPesaClient $client
    ) {}

    public function initiate(HotspotTransaction $transaction): array
    {
        $orderReference = $this->client->sanitizeOrderReference($transaction->reference);

        $response = $this->client->initiateUssdPush([
            'amount' => (string) (int) $transaction->amount,
            'currency' => 'TZS',
            'orderReference' => $orderReference,
            'phoneNumber' => $transaction->phone,
        ]);

        $gatewayReference = $response['id'] ?? $orderReference;
        $status = strtoupper((string) ($response['status'] ?? 'PROCESSING'));

        if ($status === 'FAILED') {
            throw new RuntimeException('ClickPesa rejected the payment request.');
        }

        $transaction->update([
            'status' => HotspotTransaction::STATUS_PROCESSING,
            'gateway_reference' => $gatewayReference,
        ]);

        return [
            'success' => true,
            'message' => 'Payment request sent. Check your phone and enter your Mobile Money PIN.',
            'gateway_reference' => $gatewayReference,
        ];
    }

    public function checkStatus(HotspotTransaction $transaction): array
    {
        if ($transaction->status === HotspotTransaction::STATUS_COMPLETED) {
            return ['status' => 'completed', 'message' => 'Payment confirmed.'];
        }

        if ($transaction->status === HotspotTransaction::STATUS_FAILED) {
            return ['status' => 'failed', 'message' => $transaction->failure_reason ?? 'Payment failed.'];
        }

        if ($transaction->expires_at && $transaction->expires_at->isPast()) {
            $transaction->update(['status' => HotspotTransaction::STATUS_EXPIRED]);

            return ['status' => 'expired', 'message' => 'Payment request expired.'];
        }

        $orderReference = $this->client->sanitizeOrderReference($transaction->reference);

        $payments = Cache::remember(
            'clickpesa_payment_status_'.$orderReference,
            now()->addSeconds(self::STATUS_CACHE_SECONDS),
            fn (): array => $this->client->queryPaymentStatus($orderReference)
        );

        if ($payments === []) {
            return [
                'status' => 'processing',
                'message' => 'Waiting for payment confirmation...',
            ];
        }

        $latest = collect($payments)->sortByDesc('updatedAt')->first();
        $gatewayStatus = strtoupper((string) ($latest['status'] ?? 'PROCESSING'));

        return $this->mapGatewayStatus($gatewayStatus, $latest['message'] ?? null);
    }

    public function providerName(): string
    {
        return 'clickpesa';
    }

    /**
     * @return array{status: string, message: string}
     */
    public function mapGatewayStatus(string $gatewayStatus, ?string $failureMessage = null): array
    {
        return match ($gatewayStatus) {
            'SUCCESS', 'SETTLED' => [
                'status' => 'completed',
                'message' => 'Payment confirmed.',
            ],
            'FAILED', 'REVERSED', 'REFUNDED' => [
                'status' => 'failed',
                'message' => $failureMessage ?: 'Payment failed.',
            ],
            default => [
                'status' => 'processing',
                'message' => 'Waiting for payment confirmation...',
            ],
        };
    }
}
