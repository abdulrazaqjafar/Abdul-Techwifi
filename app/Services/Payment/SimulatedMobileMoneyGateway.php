<?php

namespace App\Services\Payment;

use App\Contracts\MobileMoneyGatewayInterface;
use App\Models\HotspotTransaction;
use Illuminate\Support\Str;

class SimulatedMobileMoneyGateway implements MobileMoneyGatewayInterface
{
    public function initiate(HotspotTransaction $transaction): array
    {
        $gatewayReference = 'SIM-'.strtoupper(Str::random(12));

        $transaction->update([
            'status' => HotspotTransaction::STATUS_PROCESSING,
            'gateway_reference' => $gatewayReference,
        ]);

        return [
            'success' => true,
            'message' => 'Payment request sent. Check your phone.',
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

        $delay = config('hotspot.payment.simulated_delay_seconds', 8);
        $elapsed = $transaction->created_at->diffInSeconds(now());

        if ($elapsed >= $delay) {
            return ['status' => 'completed', 'message' => 'Payment confirmed.'];
        }

        return [
            'status' => 'processing',
            'message' => 'Waiting for payment confirmation...',
            'retry_after' => max(1, $delay - $elapsed),
        ];
    }

    public function providerName(): string
    {
        return 'simulated';
    }
}
