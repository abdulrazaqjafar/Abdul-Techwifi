@php
    $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD';
    $paymentMethods = ['cash' => 'Cash', 'mobile_money' => 'Mobile Money', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'];
@endphp

<x-layouts.app title="Sales" subtitle="View and record voucher sales">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Sales</h1>
                <p class="page-subtitle">View and record voucher sales</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('sales.create') }}" variant="primary" size="sm">Record Sale</x-button>
                <x-button href="{{ route('sales.export', request()->query()) }}" variant="outline" size="sm">Export Excel</x-button>
            </div>
        </div>
    </x-slot:header>

    <x-data-table>
        <x-slot:filters>
            <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <div>
                    <label class="label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="input-field" placeholder="Voucher, customer, notes">
                </div>
                <div>
                    <label class="label">Payment Method</label>
                    <select name="payment_method" class="input-field">
                        <option value="">All Methods</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_method'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Package</label>
                    <select name="package_id" class="input-field">
                        <option value="">All Packages</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected(($filters['package_id'] ?? '') == $package->id)>{{ $package->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Date From</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="input-field">
                </div>
                <div>
                    <label class="label">Date To</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="input-field">
                </div>
                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <x-button href="{{ route('sales.index') }}" variant="outline" size="sm">Reset</x-button>
                </div>
            </form>
        </x-slot:filters>

        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">ID</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Voucher</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Package</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Payment</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Sold At</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">Actions</th>
        </x-slot:head>

        @forelse ($sales as $sale)
            <tr class="hover:bg-surface-muted/50 dark:hover:bg-slate-800/50">
                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium dark:text-white">#{{ $sale->id }}</td>
                <td class="whitespace-nowrap px-6 py-4"><code class="text-sm text-primary-600 dark:text-primary-400">{{ $sale->voucher?->code ?? 'N/A' }}</code></td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $sale->package?->name ?? 'N/A' }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $sale->customer?->name ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-primary-600 dark:text-primary-400">{{ number_format($sale->amount, 2) }} {{ $currency }}</td>
                <td class="whitespace-nowrap px-6 py-4"><span class="badge badge-info">{{ $paymentMethods[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</span></td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text-muted">{{ $sale->sold_at?->format('M d, Y H:i') }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-right">
                    <x-button href="{{ route('sales.show', $sale) }}" variant="ghost" size="sm">View</x-button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-sm text-text-muted">No sales recorded yet.</td>
            </tr>
        @endforelse

        <x-slot:footer>{{ $sales->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
