<?php

namespace App\Repositories;

use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VoucherRepository
{
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Voucher::query()
            ->with(['package', 'admin', 'customer', 'sale'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        if (! empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('mikrotik_username', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findByCode(string $code): ?Voucher
    {
        return Voucher::query()
            ->with(['package', 'admin', 'customer', 'sale'])
            ->where('code', $code)
            ->first();
    }

    /**
     * @return Collection<int, Voucher>
     */
    public function getByStatus(string $status): Collection
    {
        return Voucher::query()
            ->with(['package', 'admin', 'customer'])
            ->where('status', $status)
            ->latest()
            ->get();
    }
}
