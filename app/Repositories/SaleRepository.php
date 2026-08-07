<?php

namespace App\Repositories;

use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleRepository
{
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Sale::query()
            ->with(['voucher', 'package', 'admin', 'customer', 'payment'])
            ->latest('sold_at');

        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('voucher', fn ($voucherQuery) => $voucherQuery->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getSalesReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Sale::query();

        if ($dateFrom !== null) {
            $query->whereDate('sold_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate('sold_at', '<=', $dateTo);
        }

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total_sales, SUM(amount) as total_revenue, AVG(amount) as average_sale')
            ->first();

        /** @var Collection<int, object> $byPaymentMethod */
        $byPaymentMethod = (clone $query)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();

        /** @var Collection<int, object> $byPackage */
        $byPackage = (clone $query)
            ->select('package_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as revenue'))
            ->with('package:id,name')
            ->groupBy('package_id')
            ->orderByDesc('revenue')
            ->get();

        /** @var Collection<int, object> $dailyBreakdown */
        $dailyBreakdown = (clone $query)
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as count, SUM(amount) as revenue')
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'total_sales' => (int) ($summary->total_sales ?? 0),
                'total_revenue' => (float) ($summary->total_revenue ?? 0),
                'average_sale' => (float) ($summary->average_sale ?? 0),
            ],
            'by_payment_method' => $byPaymentMethod,
            'by_package' => $byPackage,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }
}
