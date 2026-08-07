<x-layouts.app title="{{ $customer->name }}" subtitle="Profaili na ufuatiliaji wa huduma">
    @php
        $qualityColors = [
            'good' => 'badge-success',
            'fair' => 'badge-warning',
            'poor' => 'badge-danger',
            'offline' => 'badge-neutral',
            'inactive' => 'badge-neutral',
            'unknown' => 'badge-neutral',
        ];
        $qualityClass = $qualityColors[$serviceStatus['quality_score'] ?? 'unknown'] ?? 'badge-neutral';
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <div class="card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-secondary-800 dark:text-white">{{ $customer->name }}</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div><dt class="text-xs font-semibold uppercase text-text-muted">Simu</dt><dd class="mt-1 dark:text-white">{{ $customer->phone ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-text-muted">Email</dt><dd class="mt-1 dark:text-white">{{ $customer->email ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase text-text-muted">Vouchers / Mauzo</dt><dd class="mt-1 dark:text-white">{{ $customer->vouchers_count }} / {{ $customer->sales_count }}</dd></div>
                    </dl>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('customers.refresh-service', $customer) }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm">Sasaisha Huduma</x-button>
                    </form>
                    <x-button href="{{ route('customers.edit', $customer) }}" variant="primary" size="sm">Hariri</x-button>
                    <x-button href="{{ route('customers.index') }}" variant="outline" size="sm">Rudi</x-button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="card p-6">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Hali ya Huduma ya Internet</h3>
                <div class="mb-4">
                    <span class="badge {{ $qualityClass }}">{{ $serviceStatus['quality_label'] ?? 'Haijulikani' }}</span>
                    @if ($serviceStatus['is_online'] ?? false)
                        <span class="badge badge-success ml-2">Online</span>
                    @else
                        <span class="badge badge-neutral ml-2">Offline</span>
                    @endif
                </div>
                <dl class="grid grid-cols-1 gap-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-text-muted">Speed Limit</dt><dd class="dark:text-white">{{ $serviceStatus['rate_limit'] ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-text-muted">Data In</dt><dd class="dark:text-white">{{ number_format(($serviceStatus['bytes_in'] ?? 0) / 1048576, 2) }} MB</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-text-muted">Data Out</dt><dd class="dark:text-white">{{ number_format(($serviceStatus['bytes_out'] ?? 0) / 1048576, 2) }} MB</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-text-muted">Muda Online</dt><dd class="dark:text-white">{{ gmdate('H:i:s', $serviceStatus['uptime_seconds'] ?? 0) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-text-muted">IP</dt><dd class="dark:text-white">{{ $serviceStatus['ip_address'] ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-text-muted">Imepimwa</dt><dd class="dark:text-white">{{ ($serviceStatus['recorded_at'] ?? now())->format('M d, Y H:i') }}</dd></div>
                </dl>
                @if (! empty($serviceStatus['notes']))
                    <p class="mt-4 rounded-xl bg-surface-muted p-3 text-sm text-text-muted dark:bg-slate-800 dark:text-slate-300">{{ $serviceStatus['notes'] }}</p>
                @endif
            </div>

            <div class="card p-6">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Offers za Mwezi</h3>
                @forelse ($customer->offers as $offer)
                    <div class="mb-3 rounded-xl border border-border p-3 dark:border-slate-700">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-sm font-semibold text-primary-600">{{ $offer->offer_code }}</span>
                            <span class="badge badge-neutral">{{ ucfirst($offer->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm dark:text-white">Punguzo {{ $offer->discount_percent }}% · {{ $offer->valid_until?->format('M d, Y') }}</p>
                        @if ($offer->sms_sent_at)
                            <p class="mt-1 text-xs text-text-muted">SMS imetumwa {{ $offer->sms_sent_at->format('M d, H:i') }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-text-muted">Bado hakuna offer kwa mteja huyu.</p>
                @endforelse
            </div>
        </div>

        @if ($serviceHistory->count())
            <x-data-table title="Historia ya Ufuatiliaji wa Huduma">
                <x-slot:head>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Tarehe</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Hali</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Online</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Speed</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Data</th>
                </x-slot:head>
                @foreach ($serviceHistory as $snapshot)
                    <tr>
                        <td class="px-6 py-4 text-sm text-text-muted">{{ $snapshot->recorded_at?->format('M d, H:i') }}</td>
                        <td class="px-6 py-4"><span class="badge badge-neutral">{{ $snapshot->quality_label }}</span></td>
                        <td class="px-6 py-4 text-sm dark:text-white">{{ $snapshot->is_online ? 'Ndiyo' : 'Hapana' }}</td>
                        <td class="px-6 py-4 text-sm dark:text-white">{{ $snapshot->rate_limit ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm dark:text-white">{{ number_format(($snapshot->bytes_in + $snapshot->bytes_out) / 1048576, 2) }} MB</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif

        @if ($customer->vouchers->count())
            <x-data-table title="Vouchers za Hivi Karibuni">
                <x-slot:head>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Package</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Created</th>
                </x-slot:head>
                @foreach ($customer->vouchers as $voucher)
                    <tr>
                        <td class="px-6 py-4 text-sm text-primary-600">{{ $voucher->code }}</td>
                        <td class="px-6 py-4 text-sm dark:text-white">{{ $voucher->package?->name ?? '—' }}</td>
                        <td class="px-6 py-4"><span class="badge badge-neutral">{{ ucfirst($voucher->status) }}</span></td>
                        <td class="px-6 py-4 text-sm text-text-muted">{{ $voucher->created_at?->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </x-data-table>
        @endif
    </div>
</x-layouts.app>
