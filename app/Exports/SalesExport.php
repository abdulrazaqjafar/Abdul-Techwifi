<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = Sale::query()
            ->with(['voucher', 'package', 'admin', 'customer'])
            ->latest('sold_at');

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['payment_method'])) {
            $query->where('payment_method', $this->filters['payment_method']);
        }

        if (! empty($this->filters['package_id'])) {
            $query->where('package_id', $this->filters['package_id']);
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
            'Voucher Code',
            'Package',
            'Customer',
            'Admin',
            'Amount',
            'Payment Method',
            'Sold At',
            'Notes',
        ];
    }

    /**
     * @return list<mixed>
     */
    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->voucher?->code,
            $sale->package?->name,
            $sale->customer?->name,
            $sale->admin?->name,
            $sale->amount,
            $sale->payment_method,
            $sale->sold_at?->format('Y-m-d H:i:s'),
            $sale->notes,
        ];
    }
}
