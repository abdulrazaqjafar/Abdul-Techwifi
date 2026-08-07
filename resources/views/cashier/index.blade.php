@php $currency = $settings->currency ?? 'TZS'; @endphp

<x-layouts.app title="Cashier" subtitle="Mauzo ya cash — voucher kwa wateja">
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card title="Mauzo Leo" :value="number_format($todaySales)" icon="currency" color="primary" />
        <x-stat-card title="Mapato Leo" :value="number_format($todayRevenue, 0) . ' ' . $currency" icon="chart" color="blue" />
    </div>

    <div class="mb-8">
        <x-button href="{{ route('cashier.sell') }}" variant="primary" class="w-full sm:w-auto">
            Uza Voucher (Cash)
        </x-button>
    </div>

    <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">Mauzo ya Hivi Karibuni</h3>
            <x-button href="{{ route('cashier.sales') }}" variant="outline" size="sm">Angalia Yote</x-button>
        </div>

        @if ($recentSales->isEmpty())
            <p class="text-sm text-text-muted dark:text-slate-400">Bado hakuna mauzo leo. Anza kwa kuuza voucher.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-border text-left text-xs uppercase text-text-muted dark:border-slate-700">
                            <th class="px-3 py-2">Voucher</th>
                            <th class="px-3 py-2">Package</th>
                            <th class="px-3 py-2">Kiasi</th>
                            <th class="px-3 py-2">Muda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr class="border-b border-border/60 dark:border-slate-800">
                                <td class="px-3 py-3 font-mono font-semibold text-primary-600">
                                    <a href="{{ route('cashier.sales.show', $sale) }}" class="hover:underline">
                                        {{ $sale->voucher?->code ?? 'N/A' }}
                                    </a>
                                </td>
                                <td class="px-3 py-3 dark:text-white">{{ $sale->package?->name ?? 'N/A' }}</td>
                                <td class="px-3 py-3 dark:text-white">{{ number_format($sale->amount, 0) }} {{ $currency }}</td>
                                <td class="px-3 py-3 text-text-muted dark:text-slate-400">{{ $sale->sold_at?->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
