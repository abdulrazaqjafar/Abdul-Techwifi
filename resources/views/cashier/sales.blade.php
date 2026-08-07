@php $currency = $settings->currency ?? 'TZS'; @endphp

<x-layouts.app title="Mauzo Yangu" subtitle="Orodha ya mauzo yako ya cash">
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-surface-muted text-left text-xs uppercase text-text-muted dark:border-slate-700 dark:bg-slate-800">
                        <th class="px-4 py-3">Voucher</th>
                        <th class="px-4 py-3">Package</th>
                        <th class="px-4 py-3">Mteja</th>
                        <th class="px-4 py-3">Kiasi</th>
                        <th class="px-4 py-3">Tarehe</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr class="border-b border-border/60 dark:border-slate-800">
                            <td class="px-4 py-3 font-mono font-semibold text-primary-600">{{ $sale->voucher?->code ?? 'N/A' }}</td>
                            <td class="px-4 py-3 dark:text-white">{{ $sale->package?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 dark:text-white">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-3 dark:text-white">{{ number_format($sale->amount, 0) }} {{ $currency }}</td>
                            <td class="px-4 py-3 text-text-muted dark:text-slate-400">{{ $sale->sold_at?->format('M d, Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-button href="{{ route('cashier.sales.show', $sale) }}" variant="outline" size="sm">Angalia</x-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-text-muted dark:text-slate-400">Hakuna mauzo bado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="border-t border-border px-4 py-3 dark:border-slate-700">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
