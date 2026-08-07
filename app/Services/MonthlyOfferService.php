<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\CustomerOffer;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MonthlyOfferService
{
    public function __construct(
        protected SmsService $smsService,
        protected ActivityLogService $activityLogService
    ) {}

    public static function isPenultimateDayOfMonth(?\Carbon\Carbon $date = null): bool
    {
        $date ??= now();
        $penultimateDay = $date->copy()->endOfMonth()->subDay()->day;

        return $date->day === $penultimateDay;
    }

    /**
     * @return array{created: int, sent: int, skipped: int}
     */
    public function dispatchMonthlyOffers(?\Carbon\Carbon $date = null, bool $force = false): array
    {
        $date ??= now();
        $settings = BusinessSetting::getSettings();

        if (! $settings->monthly_offer_enabled) {
            return ['created' => 0, 'sent' => 0, 'skipped' => 0];
        }

        if (! $force && ! self::isPenultimateDayOfMonth($date)) {
            return ['created' => 0, 'sent' => 0, 'skipped' => 0];
        }

        $monthKey = $date->format('Y-m');
        $validFrom = $date->copy()->startOfDay();
        $validUntil = $date->copy()->endOfMonth()->endOfDay();
        $discount = (int) ($settings->monthly_offer_discount_percent ?: 10);

        $created = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($this->eligibleCustomers() as $customer) {
            if (! $customer->phone) {
                $skipped++;

                continue;
            }

            $existing = CustomerOffer::query()
                ->where('customer_id', $customer->id)
                ->where('month_key', $monthKey)
                ->first();

            if ($existing) {
                if ($existing->sms_sent_at === null) {
                    $this->sendOfferSms($existing, $settings);
                    $sent++;
                } else {
                    $skipped++;
                }

                continue;
            }

            $offer = CustomerOffer::query()->create([
                'customer_id' => $customer->id,
                'offer_code' => $this->generateOfferCode(),
                'discount_percent' => $discount,
                'message' => $this->buildMessage($customer, $discount, '', $settings, $validUntil),
                'status' => CustomerOffer::STATUS_PENDING,
                'month_key' => $monthKey,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
            ]);

            $offer->update([
                'message' => $this->buildMessage($customer, $discount, $offer->offer_code, $settings, $validUntil),
            ]);

            $this->sendOfferSms($offer->fresh(), $settings);
            $created++;
            $sent++;
        }

        $this->activityLogService->log(
            'offers.monthly_dispatched',
            "Monthly offers: created {$created}, SMS sent {$sent}, skipped {$skipped}."
        );

        return compact('created', 'sent', 'skipped');
    }

    /**
     * @return Collection<int, Customer>
     */
    protected function eligibleCustomers(): Collection
    {
        $this->syncCustomersFromVouchers();

        return Customer::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->get();
    }

    protected function syncCustomersFromVouchers(): void
    {
        Voucher::query()
            ->whereNotNull('phone_number')
            ->whereNull('customer_id')
            ->select(['id', 'phone_number'])
            ->chunkById(100, function ($vouchers): void {
                foreach ($vouchers as $voucher) {
                    $customer = Customer::query()->firstOrCreate(
                        ['phone' => $voucher->phone_number],
                        ['name' => 'Mteja '.substr($voucher->phone_number, -4)]
                    );

                    $voucher->update(['customer_id' => $customer->id]);
                }
            });
    }

    protected function sendOfferSms(CustomerOffer $offer, BusinessSetting $settings): void
    {
        $customer = $offer->customer;

        if (! $customer?->phone) {
            throw new RuntimeException('Customer phone is required for SMS.');
        }

        DB::transaction(function () use ($offer, $customer, $settings): void {
            $message = $offer->message ?: $this->buildMessage(
                $customer,
                $offer->discount_percent,
                $offer->offer_code,
                $settings,
                $offer->valid_until
            );

            $this->smsService->send($customer->phone, $message, $customer);

            $offer->update([
                'message' => $message,
                'status' => CustomerOffer::STATUS_SENT,
                'sms_sent_at' => now(),
            ]);
        });
    }

    protected function buildMessage(
        Customer $customer,
        int $discount,
        string $code,
        BusinessSetting $settings,
        \Carbon\Carbon $expires
    ): string {
        $template = $settings->monthly_offer_message ?: $settings->defaultOfferMessage();

        return str_replace(
            ['{name}', '{business}', '{discount}', '{code}', '{expires}'],
            [
                $customer->name,
                $settings->business_name,
                (string) $discount,
                $code,
                $expires->format('d/m/Y'),
            ],
            $template
        );
    }

    protected function generateOfferCode(): string
    {
        do {
            $code = 'OFFER-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (CustomerOffer::query()->where('offer_code', $code)->exists());

        return $code;
    }
}
