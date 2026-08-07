@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'TZS'; @endphp

<x-layouts.app title="{{ $staff->name }}" subtitle="Maelezo na mauzo ya mfanyakazi">
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card title="Mauzo Leo" :value="number_format($todaySales)" icon="currency" color="primary" />
        <x-stat-card title="Mapato Leo" :value="number_format($todayRevenue, 0) . ' ' . $currency" icon="chart" color="blue" />
        <x-stat-card title="Jumla Mauzo" :value="number_format($staff->sales_count)" icon="ticket" color="secondary" />
    </div>

    <div class="mb-6 card p-6">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-text-muted">Jina</dt>
                <dd class="mt-1 dark:text-white">{{ $staff->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-text-muted">Email</dt>
                <dd class="mt-1 dark:text-white">{{ $staff->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-text-muted">Hali</dt>
                <dd class="mt-1">
                    @if ($staff->is_active)
                        <span class="badge badge-success">Hai</span>
                    @else
                        <span class="badge badge-danger">Imesitishwa</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-text-muted">Imesajiliwa</dt>
                <dd class="mt-1 dark:text-white">{{ $staff->created_at?->format('M d, Y H:i') }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-3">
            <x-button href="{{ route('staff.edit', $staff) }}" variant="primary">Hariri</x-button>
            <x-button href="{{ route('staff.index') }}" variant="outline">Rudi</x-button>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Mauzo ya Hivi Karibuni</h3>

        @if ($recentSales->isEmpty())
            <p class="text-sm text-text-muted dark:text-slate-400">Bado hakuna mauzo.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-border text-left text-xs uppercase text-text-muted dark:border-slate-700">
                            <th class="px-3 py-2">Voucher</th>
                            <th class="px-3 py-2">Package</th>
                            <th class="px-3 py-2">Kiasi</th>
                            <th class="px-3 py-2">Tarehe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr class="border-b border-border/60 dark:border-slate-800">
                                <td class="px-3 py-3 font-mono font-semibold text-primary-600">{{ $sale->voucher?->code ?? 'N/A' }}</td>
                                <td class="px-3 py-3 dark:text-white">{{ $sale->package?->name ?? 'N/A' }}</td>
                                <td class="px-3 py-3 dark:text-white">{{ number_format($sale->amount, 0) }} {{ $currency }}</td>
                                <td class="px-3 py-3 text-text-muted dark:text-slate-400">{{ $sale->sold_at?->format('M d, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.app>
