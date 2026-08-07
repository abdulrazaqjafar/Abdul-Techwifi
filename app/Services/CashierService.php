<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CashierService
{
    public function __construct(
        protected VoucherService $voucherService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * @param  array{package_id: int, admin_id: int, amount?: float|null, customer_name?: string|null, phone?: string|null, notes?: string|null}  $data
     */
    public function sellForCash(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $customerId = $this->resolveCustomerId(
                $data['customer_name'] ?? null,
                $data['phone'] ?? null
            );

            $voucher = $this->voucherService->generateSingle(
                $data['package_id'],
                $data['admin_id'],
                $customerId
            );

            if (! empty($data['phone'])) {
                $voucher->update(['phone_number' => $this->normalizePhone($data['phone'])]);
            }

            $amount = $data['amount'] ?? $voucher->package->price;

            $sale = Sale::query()->create([
                'voucher_id' => $voucher->id,
                'package_id' => $voucher->package_id,
                'admin_id' => $data['admin_id'],
                'customer_id' => $customerId,
                'amount' => $amount,
                'payment_method' => 'cash',
                'sold_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            Payment::query()->create([
                'sale_id' => $sale->id,
                'amount' => $amount,
                'method' => 'cash',
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $voucher->update(['source' => 'cashier']);

            $this->activityLogService->log(
                'cashier.sale',
                "Cash sale #{$sale->id} — voucher {$voucher->code}.",
                $data['admin_id']
            );

            return $sale->load(['voucher.package', 'package', 'customer', 'payment']);
        });
    }

    protected function resolveCustomerId(?string $name, ?string $phone): ?int
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $customer = Customer::query()->firstOrCreate(
            ['name' => trim($name)],
            ['phone' => $phone ? $this->normalizePhone($phone) : null]
        );

        if ($phone && ! $customer->phone) {
            $customer->update(['phone' => $this->normalizePhone($phone)]);
        }

        return $customer->id;
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
}
