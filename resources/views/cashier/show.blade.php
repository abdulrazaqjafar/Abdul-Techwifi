@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'TZS'; @endphp

<x-layouts.app title="Mauzo #{{ $sale->id }}" subtitle="Maelezo ya mauzo ya cash">
    <div class="mx-auto max-w-3xl">
        <div class="card p-6">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Voucher Code</dt>
                    <dd class="mt-1 font-mono text-lg font-bold text-primary-600">{{ $sale->voucher?->code ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Kiasi</dt>
                    <dd class="mt-1 text-lg font-bold dark:text-white">{{ number_format($sale->amount, 0) }} {{ $currency }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Package</dt>
                    <dd class="mt-1 dark:text-white">{{ $sale->package?->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Malipo</dt>
                    <dd class="mt-1 capitalize dark:text-white">Cash</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Mteja</dt>
                    <dd class="mt-1 dark:text-white">{{ $sale->customer?->name ?? 'Walk-in' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Imeuza</dt>
                    <dd class="mt-1 dark:text-white">{{ $sale->sold_at?->format('M d, Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">MAC (Activation)</dt>
                    <dd class="mt-1 font-mono text-sm dark:text-white">{{ $sale->voucher?->activation_mac ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">IP (Activation)</dt>
                    <dd class="mt-1 font-mono text-sm dark:text-white">{{ $sale->voucher?->activation_ip ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Kifaa</dt>
                    <dd class="mt-1 dark:text-white">{{ $sale->voucher?->device_summary ?? '—' }}</dd>
                </div>
                @if ($sale->voucher?->device_hostname)
                    <div>
                        <dt class="text-xs font-semibold uppercase text-text-muted">Jina la Kifaa</dt>
                        <dd class="mt-1 dark:text-white">{{ $sale->voucher->device_hostname }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase text-text-muted">Imeamilishwa</dt>
                    <dd class="mt-1 dark:text-white">{{ $sale->voucher?->activated_at?->format('M d, Y H:i') ?? 'Bado haijaamilishwa' }}</dd>
                </div>
            </dl>

            @if ($sale->notes)
                <div class="mt-6 rounded-xl bg-surface-muted p-4 dark:bg-slate-800">
                    <dt class="text-xs font-semibold uppercase text-text-muted">Maelezo</dt>
                    <dd class="mt-1 text-sm dark:text-slate-300">{{ $sale->notes }}</dd>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                <x-button href="{{ route('cashier.sales.print', $sale) }}" variant="primary" target="_blank">Chapisha Risiti</x-button>
                <x-button href="{{ route('cashier.sell') }}" variant="secondary">Uza Tena</x-button>
                <x-button href="{{ route('cashier.sales') }}" variant="outline">Rudi kwa Mauzo</x-button>
            </div>
        </div>
    </div>
</x-layouts.app>
