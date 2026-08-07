<?php

namespace App\Exports;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VouchersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = Voucher::query()
            ->with(['package', 'admin', 'customer'])
            ->latest();

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['package_id'])) {
            $query->where('package_id', $this->filters['package_id']);
        }

        if (! empty($this->filters['batch_id'])) {
            $query->where('batch_id', $this->filters['batch_id']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Package',
            'Status',
            'Customer',
            'Admin',
            'MikroTik Username',
            'Batch ID',
            'Activated At',
            'Expires At',
            'Created At',
        ];
    }

    /**
     * @return list<mixed>
     */
    public function map($voucher): array
    {
        return [
            $voucher->id,
            $voucher->code,
            $voucher->package?->name,
            $voucher->status,
            $voucher->customer?->name,
            $voucher->admin?->name,
            $voucher->mikrotik_username,
            $voucher->batch_id,
            $voucher->activated_at?->format('Y-m-d H:i:s'),
            $voucher->expires_at?->format('Y-m-d H:i:s'),
            $voucher->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
