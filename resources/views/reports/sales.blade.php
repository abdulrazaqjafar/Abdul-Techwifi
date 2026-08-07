@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD'; @endphp

<x-layouts.app title="Sales Report" subtitle="{{ $validated['date_from'] }} to {{ $validated['date_to'] }}">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Sales Report</h1>
                <p class="page-subtitle">{{ $validated['date_from'] }} to {{ $validated['date_to'] }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('reports.export.pdf', ['type' => 'sales', 'date_from' => $validated['date_from'], 'date_to' => $validated['date_to']]) }}" variant="outline" size="sm">Export PDF</x-button>
                <x-button href="{{ route('reports.export.excel', ['type' => 'sales']) }}" variant="primary" size="sm">Export Excel</x-button>
            </div>
        </div>
    </x-slot:header>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-4 p-4">
        <div><label class="label">From</label><input type="date" name="date_from" value="{{ $validated['date_from'] }}" class="input-field"></div>
        <div><label class="label">To</label><input type="date" name="date_to" value="{{ $validated['date_to'] }}" class="input-field"></div>
        <x-button type="submit" variant="primary" size="sm">Apply</x-button>
    </form>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card title="Total Sales" :value="number_format($report['summary']['total_sales'])" icon="chart" />
        <x-stat-card title="Total Revenue" :value="number_format($report['summary']['total_revenue'], 2) . ' ' . $currency" icon="currency" color="primary" />
        <x-stat-card title="Average Sale" :value="number_format($report['summary']['average_sale'], 2) . ' ' . $currency" icon="currency" color="blue" />
    </div>

    <x-data-table title="Sales Records">
        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">ID</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Voucher</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Package</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Amount</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Payment</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Sold At</th>
        </x-slot:head>
        @forelse ($sales as $sale)
            <tr>
                <td class="px-6 py-4 text-sm dark:text-white">#{{ $sale->id }}</td>
                <td class="px-6 py-4 text-sm text-primary-600">{{ $sale->voucher?->code ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $sale->package?->name ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $sale->customer?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm font-semibold text-primary-600">{{ number_format($sale->amount, 2) }} {{ $currency }}</td>
                <td class="px-6 py-4 text-sm capitalize dark:text-slate-300">{{ str_replace('_', ' ', $sale->payment_method) }}</td>
                <td class="px-6 py-4 text-sm text-text-muted">{{ $sale->sold_at?->format('M d, Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-text-muted">No sales found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $sales->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
