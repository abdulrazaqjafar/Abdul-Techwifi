<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = Customer::query()
            ->withCount(['vouchers', 'sales'])
            ->latest();

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
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
            'Name',
            'Phone',
            'Email',
            'Vouchers Count',
            'Sales Count',
            'Notes',
            'Created At',
        ];
    }

    /**
     * @return list<mixed>
     */
    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->vouchers_count,
            $customer->sales_count,
            $customer->notes,
            $customer->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
