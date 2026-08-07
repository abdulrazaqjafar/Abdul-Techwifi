@php
    $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD';
    $highlightVoucherId = (int) session('highlight_voucher_id');
    $statusColors = [
        'unused' => 'badge-success',
        'used' => 'badge-info',
        'expired' => 'badge-warning',
        'cancelled' => 'badge-danger',
    ];
@endphp

<x-layouts.app title="Vouchers" subtitle="Generate, search, and manage Wi-Fi vouchers">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Vouchers</h1>
                <p class="page-subtitle">Generate, search, and manage Wi-Fi vouchers</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('vouchers.generate.form') }}" variant="primary" size="sm">
                    Generate Vouchers
                </x-button>
                <x-button href="{{ route('vouchers.export.excel', request()->query()) }}" variant="outline" size="sm">Export Excel</x-button>
            </div>
        </div>
    </x-slot:header>

    <x-data-table>
        <x-slot:filters>
            <form method="GET" action="{{ route('vouchers.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="input-field" placeholder="Code or username">
                </div>
                <div>
                    <label class="label">Status</label>
                    <select name="status" class="input-field">
                        <option value="">All Statuses</option>
                        @foreach (['unused', 'used', 'expired', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
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
                <div class="flex items-end gap-2 lg:col-span-5">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <x-button href="{{ route('vouchers.index') }}" variant="outline" size="sm">Reset</x-button>
                </div>
            </form>

            <div class="mt-4 flex flex-wrap gap-2 border-t border-border pt-4 dark:border-slate-700">
                <span class="self-center text-sm font-medium text-text-muted dark:text-slate-400">Quick Generate:</span>
                @foreach ([100, 200, 500, 1000] as $count)
                    <form method="POST" action="{{ route('vouchers.generate-bulk') }}" class="inline" @submit="$dispatch('form-submitting')">
                        @csrf
                        <input type="hidden" name="count" value="{{ $count }}">
                        <input type="hidden" name="package_id" value="{{ $packages->first()?->id }}">
                        <x-button type="submit" variant="secondary" size="sm" onclick="return confirm('Generate {{ $count }} vouchers for the first active package?')">
                            {{ $count }} Vouchers
                        </x-button>
                    </form>
                @endforeach
            </div>
        </x-slot:filters>

        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Code</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Package</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">MAC (Activation)</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">IP (Activation)</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Kifaa</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Source</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Activated</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Created</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">Actions</th>
        </x-slot:head>

        @forelse ($vouchers as $voucher)
            <tr @class([
                'hover:bg-surface-muted/50 dark:hover:bg-slate-800/50',
                'bg-primary-50 ring-1 ring-inset ring-primary-300 dark:bg-primary-900/20 dark:ring-primary-700' => $voucher->id === $highlightVoucherId,
            ])>
                <td class="whitespace-nowrap px-6 py-4">
                    <code class="rounded-lg bg-surface-muted px-2 py-1 text-sm font-semibold text-primary-600 dark:bg-slate-800 dark:text-primary-400">{{ $voucher->code }}</code>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">
                    {{ $voucher->package?->name ?? 'N/A' }}
                    <div class="text-xs text-text-muted">{{ number_format($voucher->package?->price ?? 0, 2) }} {{ $currency }}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                    <span class="badge {{ $statusColors[$voucher->status] ?? 'badge-neutral' }}">{{ ucfirst($voucher->status) }}</span>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $voucher->customer?->name ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 font-mono text-xs dark:text-slate-300">{{ $voucher->activation_mac ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 font-mono text-xs dark:text-slate-300">{{ $voucher->activation_ip ?? '—' }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">
                    {{ $voucher->device_summary }}
                    @if ($voucher->device_hostname)
                        <div class="text-xs text-text-muted">{{ $voucher->device_hostname }}</div>
                    @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm capitalize dark:text-slate-300">{{ $voucher->source ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text-muted">{{ $voucher->activated_at?->format('M d, H:i') ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text-muted">{{ $voucher->created_at?->format('M d, Y H:i') }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if ($voucher->isUnused())
                            <x-button href="{{ route('vouchers.print', ['voucher_ids' => [$voucher->id]]) }}" variant="ghost" size="sm" target="_blank">Print</x-button>
                        @endif
                        <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" x-ref="del{{ $voucher->id }}" @submit.prevent="$dispatch('confirm-dialog', { title: 'Delete Voucher', message: 'Delete voucher {{ $voucher->code }}?', form: $refs.del{{ $voucher->id }} })">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="px-6 py-12 text-center text-sm text-text-muted">No vouchers found.</td>
            </tr>
        @endforelse

        <x-slot:footer>{{ $vouchers->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
