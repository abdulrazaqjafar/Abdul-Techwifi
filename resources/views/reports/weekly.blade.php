@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD'; @endphp

<x-layouts.app title="Weekly Sales Report" subtitle="{{ $filters['date_from'] }} to {{ $filters['date_to'] }}">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Weekly Sales Report</h1>
                <p class="page-subtitle">{{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('reports.export.pdf', ['type' => 'weekly', 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]) }}" variant="outline" size="sm">Export PDF</x-button>
                <x-button href="{{ route('reports.export.excel', ['type' => 'weekly']) }}" variant="primary" size="sm">Export Excel</x-button>
            </div>
        </div>
    </x-slot:header>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-4 p-4">
        <div><label class="label">From</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="input-field"></div>
        <div><label class="label">To</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="input-field"></div>
        <x-button type="submit" variant="primary" size="sm">Apply</x-button>
    </form>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card title="Total Sales" :value="number_format($report['summary']['total_sales'])" icon="chart" />
        <x-stat-card title="Total Revenue" :value="number_format($report['summary']['total_revenue'], 2) . ' ' . $currency" icon="currency" color="primary" />
        <x-stat-card title="Average Sale" :value="number_format($report['summary']['average_sale'], 2) . ' ' . $currency" icon="currency" color="blue" />
    </div>

    <div class="card mb-6 p-6">
        <h3 class="mb-4 text-lg font-semibold dark:text-white">Weekly Trend</h3>
        <div class="h-64" x-data="chartWidget({ type: 'line', data: { labels: @js($chart['labels']), datasets: [{ label: 'Revenue', data: @js($chart['revenues']), borderColor: '#1e3a5f', backgroundColor: 'rgba(30,58,95,0.1)', fill: true, tension: 0.4 }] }, scales: { y: { beginAtZero: true } } })">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-data-table title="By Payment Method">
            <x-slot:head>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Method</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Count</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Revenue</th>
            </x-slot:head>
            @foreach ($report['by_payment_method'] as $row)
                <tr>
                    <td class="px-6 py-4 text-sm capitalize dark:text-slate-300">{{ str_replace('_', ' ', $row->payment_method) }}</td>
                    <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $row->count }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-primary-600">{{ number_format($row->revenue, 2) }} {{ $currency }}</td>
                </tr>
            @endforeach
        </x-data-table>

        <x-data-table title="By Package">
            <x-slot:head>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Package</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Count</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Revenue</th>
            </x-slot:head>
            @foreach ($report['by_package'] as $row)
                <tr>
                    <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $row->package?->name ?? 'Unknown' }}</td>
                    <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $row->count }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-primary-600">{{ number_format($row->revenue, 2) }} {{ $currency }}</td>
                </tr>
            @endforeach
        </x-data-table>
    </div>
</x-layouts.app>
