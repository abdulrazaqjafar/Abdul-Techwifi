@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD'; @endphp

<x-layouts.app title="Sale #{{ $sale->id }}" subtitle="Sale details">
    <div class="mx-auto max-w-3xl">
        <div class="card p-6">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Voucher Code</dt><dd class="mt-1 font-mono text-lg font-bold text-primary-600">{{ $sale->voucher?->code ?? 'N/A' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Amount</dt><dd class="mt-1 text-lg font-bold dark:text-white">{{ number_format($sale->amount, 2) }} {{ $currency }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Package</dt><dd class="mt-1 dark:text-white">{{ $sale->package?->name ?? 'N/A' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Payment Method</dt><dd class="mt-1 capitalize dark:text-white">{{ str_replace('_', ' ', $sale->payment_method) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Customer</dt><dd class="mt-1 dark:text-white">{{ $sale->customer?->name ?? 'Walk-in' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Sold By</dt><dd class="mt-1 dark:text-white">{{ $sale->admin?->name ?? 'N/A' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Sold At</dt><dd class="mt-1 dark:text-white">{{ $sale->sold_at?->format('M d, Y H:i') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Payment Status</dt><dd class="mt-1"><span class="badge badge-success">{{ ucfirst($sale->payment?->status ?? 'completed') }}</span></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">MAC (Activation)</dt><dd class="mt-1 font-mono text-sm dark:text-white">{{ $sale->voucher?->activation_mac ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">IP (Activation)</dt><dd class="mt-1 font-mono text-sm dark:text-white">{{ $sale->voucher?->activation_ip ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Kifaa</dt><dd class="mt-1 dark:text-white">{{ $sale->voucher?->device_summary ?? '—' }}</dd></div>
                @if ($sale->voucher?->device_hostname)
                    <div><dt class="text-xs font-semibold uppercase text-text-muted">Jina la Kifaa</dt><dd class="mt-1 dark:text-white">{{ $sale->voucher->device_hostname }}</dd></div>
                @endif
                <div><dt class="text-xs font-semibold uppercase text-text-muted">Activated At</dt><dd class="mt-1 dark:text-white">{{ $sale->voucher?->activated_at?->format('M d, Y H:i') ?? 'Bado haijaamilishwa' }}</dd></div>
            </dl>
            @if ($sale->notes)
                <div class="mt-6 rounded-xl bg-surface-muted p-4 dark:bg-slate-800">
                    <dt class="text-xs font-semibold uppercase text-text-muted">Notes</dt>
                    <dd class="mt-1 text-sm dark:text-slate-300">{{ $sale->notes }}</dd>
                </div>
            @endif
            <div class="mt-6"><x-button href="{{ route('sales.index') }}" variant="outline">Back to Sales</x-button></div>
        </div>
    </div>
</x-layouts.app>
