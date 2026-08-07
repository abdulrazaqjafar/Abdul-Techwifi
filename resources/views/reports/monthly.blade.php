@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD'; @endphp

<x-layouts.app title="Monthly Sales Report" subtitle="{{ $filters['date_from'] }} to {{ $filters['date_to'] }}">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Monthly Sales Report</h1>
                <p class="page-subtitle">{{ $filters['date_from'] }} to {{ $filters['date_to'] }}</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('reports.export.pdf', ['type' => 'monthly', 'date_from' => $filters['date_from'], 'date_to' => $filters['date_to']]) }}" variant="outline" size="sm">Export PDF</x-button>
                <x-button href="{{ route('reports.export.excel', ['type' => 'monthly']) }}" variant="primary" size="sm">Export Excel</x-button>
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
        <h3 class="mb-4 text-lg font-semibold dark:text-white">Monthly Trend</h3>
        <div class="h-64" x-data="chartWidget({ type: 'bar', data: { labels: @js($chart['labels']), datasets: [{ label: 'Revenue', data: @js($chart['revenues']), backgroundColor: '#16a34a', borderRadius: 6 }] }, scales: { y: { beginAtZero: true } } })">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <x-data-table title="Daily Breakdown">
        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Date</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Sales</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Revenue</th>
        </x-slot:head>
        @forelse ($report['daily_breakdown'] as $row)
            <tr>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $row->date }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $row->count }}</td>
                <td class="px-6 py-4 text-sm font-semibold text-primary-600">{{ number_format($row->revenue, 2) }} {{ $currency }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-6 py-8 text-center text-sm text-text-muted">No data available.</td></tr>
        @endforelse
    </x-data-table>
</x-layouts.app>
