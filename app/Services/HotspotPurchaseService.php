<?php

namespace App\Services;

use App\Models\HotspotTransaction;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Voucher;
use App\Services\Payment\MobileMoneyGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class HotspotPurchaseService
{
    public function __construct(
        protected MobileMoneyGatewayManager $gatewayManager,
        protected VoucherService $voucherService
    ) {}

    public function initiatePurchase(
        Package $package,
        string $phone,
        string $provider,
        array $session = []
    ): HotspotTransaction {
        if (! $package->is_active) {
            throw new RuntimeException(__('hotspot.errors.inactive_package'));
        }

        $defaultProvider = config('hotspot.payment.default_provider', 'simulated');
        if ($defaultProvider !== 'simulated') {
            $provider = $defaultProvider;
        }

        $this->validatePaymentAmount((float) $package->price, $provider);

        $reference = 'TS'.strtoupper(Str::random(10));
        $timeout = config('hotspot.payment.timeout_seconds', 120);

        $transaction = HotspotTransaction::query()->create([
            'reference' => $reference,
            'package_id' => $package->id,
            'phone' => $this->normalizePhone($phone),
            'provider' => $provider,
            'amount' => $package->price,
            'status' => HotspotTransaction::STATUS_PENDING,
            'mac_address' => $session['mac'] ?? null,
            'ip_address' => $session['ip'] ?? null,
            'link_login' => $session['link_login'] ?? null,
            'link_orig' => $session['link_orig'] ?? null,
            'expires_at' => now()->addSeconds($timeout),
        ]);

        $gateway = $this->gatewayManager->driver($provider);

        try {
            $gateway->initiate($transaction);
        } catch (\Throwable $exception) {
            // Without this the row stays "pending" forever with no explanation,
            // which is why dozens of failed attempts looked identical.
            $transaction->update([
                'status' => HotspotTransaction::STATUS_FAILED,
                'failure_reason' => Str::limit($exception->getMessage(), 250),
            ]);

            throw new RuntimeException($this->explainGatewayError($exception->getMessage()), 0, $exception);
        }

        return $transaction->fresh(['package']);
    }

    /**
     * Turn the gateway's English error into something a customer standing at
     * the counter can act on.
     */
    public function explainGatewayError(string $message): string
    {
        $haystack = strtolower($message);

        if (str_contains($haystack, 'not active')) {
            return __('hotspot.errors.method_inactive');
        }

        if (str_contains($haystack, 'daily api limit') || str_contains($haystack, '429')) {
            Log::critical('ClickPesa daily API limit reached', ['message' => $message]);

            return __('hotspot.errors.gateway_limit');
        }

        if (str_contains($haystack, 'insufficient')) {
            return __('hotspot.errors.insufficient_balance');
        }

        return $message;
    }

    public function checkPaymentStatus(string $reference): array
    {
        $transaction = HotspotTransaction::query()
            ->with(['package', 'voucher'])
            ->where('reference', $reference)
            ->firstOrFail();

        if ($transaction->isCompleted() && $transaction->voucher) {
            return $this->buildCompletedResponse($transaction);
        }

        $gateway = $this->gatewayManager->driver($transaction->provider);
        $status = $gateway->checkStatus($transaction);

        if ($status['status'] === 'completed' && ! $transaction->isCompleted()) {
            $transaction = $this->completeTransaction($transaction);

            return $this->buildCompletedResponse($transaction);
        }

        if ($status['status'] === 'failed') {
            $transaction->update([
                'status' => HotspotTransaction::STATUS_FAILED,
                'failure_reason' => $status['message'] ?? 'Payment failed.',
            ]);
        }

        if ($status['status'] === 'expired') {
            $transaction->update(['status' => HotspotTransaction::STATUS_EXPIRED]);
        }

        return [
            'status' => $status['status'],
            'message' => $status['message'] ?? '',
            'transaction' => $transaction->fresh(['package', 'voucher']),
        ];
    }

    public function processPaymentNotification(string $reference, string $gatewayStatus, ?string $failureReason = null): ?HotspotTransaction
    {
        $transaction = HotspotTransaction::query()
            ->with(['package', 'voucher'])
            ->where('reference', $reference)
            ->first();

        if (! $transaction) {
            return null;
        }

        if ($transaction->isCompleted()) {
            return $transaction;
        }

        $normalizedStatus = strtoupper($gatewayStatus);

        if (in_array($normalizedStatus, ['SUCCESS', 'SETTLED'], true)) {
            return $this->completeTransaction($transaction);
        }

        if (in_array($normalizedStatus, ['FAILED', 'REVERSED', 'REFUNDED'], true)) {
            $transaction->update([
                'status' => HotspotTransaction::STATUS_FAILED,
                'failure_reason' => $failureReason ?? 'Payment failed.',
            ]);
        }

        return $transaction->fresh(['package', 'voucher']);
    }

    protected function completeTransaction(HotspotTransaction $transaction): HotspotTransaction
    {
        return DB::transaction(function () use ($transaction): HotspotTransaction {
            $transaction = HotspotTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->isCompleted()) {
                return $transaction->load(['package', 'voucher']);
            }

            $customer = \App\Models\Customer::query()->firstOrCreate(
                ['phone' => $transaction->phone],
                ['name' => 'Mteja '.substr($transaction->phone, -4)]
            );

            $code = $this->voucherService->generateCode();

            $voucher = Voucher::query()->create([
                'code' => $code,
                'package_id' => $transaction->package_id,
                'status' => Voucher::STATUS_UNUSED,
                'customer_id' => $customer->id,
                'phone_number' => $transaction->phone,
                'payment_reference' => $transaction->reference,
                'source' => 'hotspot',
                // Matches the code so the router's own login page accepts it.
                'mikrotik_username' => $code,
            ]);

            $this->voucherService->syncToRouter($voucher, isNewCode: true);

            $sale = Sale::query()->create([
                'voucher_id' => $voucher->id,
                'package_id' => $transaction->package_id,
                'admin_id' => \App\Models\Admin::query()->value('id') ?? 1,
                'customer_id' => $customer->id,
                'amount' => $transaction->amount,
                'payment_method' => $transaction->provider,
                'sold_at' => now(),
                'notes' => 'Hotspot walk-in purchase · '.$transaction->phone,
            ]);

            Payment::query()->create([
                'sale_id' => $sale->id,
                'amount' => $transaction->amount,
                'method' => $transaction->provider,
                'reference' => $transaction->reference,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $transaction->update([
                'status' => HotspotTransaction::STATUS_COMPLETED,
                'voucher_id' => $voucher->id,
                'customer_id' => $customer->id,
                'paid_at' => now(),
            ]);

            return $transaction->fresh(['package', 'voucher']);
        });
    }

    protected function buildCompletedResponse(HotspotTransaction $transaction): array
    {
        $voucher = $transaction->voucher;
        $package = $transaction->package;

        return [
            'status' => 'completed',
            'message' => __('hotspot.payment.success'),
            'transaction' => $transaction,
            'voucher' => [
                'code' => $voucher->code,
                'package_name' => $package->name,
                'duration' => $package->getDurationLabel(),
                'price' => number_format((float) $package->price, 0),
                'expires_at' => $voucher->expires_at?->toIso8601String(),
            ],
        ];
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        return $digits;
    }

    protected function validatePaymentAmount(float $amount, string $provider): void
    {
        if ($provider !== 'clickpesa') {
            return;
        }

        $min = (int) config('hotspot.providers.clickpesa.min_amount', 908);
        $max = (int) config('hotspot.providers.clickpesa.max_amount', 3000000);

        if ($amount < $min || $amount > $max) {
            throw new RuntimeException(__('hotspot.errors.amount_out_of_range', [
                'min' => number_format($min),
                'max' => number_format($max),
            ]));
        }
    }
}
