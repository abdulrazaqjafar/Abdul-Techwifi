@php
    $statusColors = ['unused' => 'badge-success', 'used' => 'badge-info', 'expired' => 'badge-warning', 'cancelled' => 'badge-danger'];
    $packages = \App\Models\Package::query()->orderBy('name')->get();
@endphp

<x-layouts.app title="Voucher Report" subtitle="Voucher status summary and detailed listing">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Voucher Report</h1>
                <p class="page-subtitle">Voucher status summary and detailed listing</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('reports.export.pdf', array_merge(['type' => 'vouchers'], $validated)) }}" variant="outline" size="sm">Export PDF</x-button>
                <x-button href="{{ route('reports.export.excel', array_merge(['type' => 'vouchers'], $validated)) }}" variant="primary" size="sm">Export Excel</x-button>
            </div>
        </div>
    </x-slot:header>

    <form method="GET" class="card mb-6 grid grid-cols-1 gap-4 p-4 md:grid-cols-4">
        <div>
            <label class="label">Status</label>
            <select name="status" class="input-field">
                <option value="">All</option>
                @foreach (['unused', 'used', 'expired', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(($validated['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Package</label>
            <select name="package_id" class="input-field">
                <option value="">All</option>
                @foreach ($packages as $pkg)
                    <option value="{{ $pkg->id }}" @selected(($validated['package_id'] ?? '') == $pkg->id)>{{ $pkg->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label">From</label><input type="date" name="date_from" value="{{ $validated['date_from'] ?? '' }}" class="input-field"></div>
        <div><label class="label">To</label><input type="date" name="date_to" value="{{ $validated['date_to'] ?? '' }}" class="input-field"></div>
        <div class="md:col-span-4"><x-button type="submit" variant="primary" size="sm">Apply Filters</x-button></div>
    </form>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <x-stat-card title="Total" :value="number_format($summary['total'])" icon="ticket" />
        <x-stat-card title="Unused" :value="number_format($summary['unused'])" icon="ticket" color="primary" />
        <x-stat-card title="Used" :value="number_format($summary['used'])" icon="ticket" color="blue" />
        <x-stat-card title="Expired" :value="number_format($summary['expired'])" icon="ticket" color="amber" />
        <x-stat-card title="Cancelled" :value="number_format($summary['cancelled'])" icon="ticket" color="red" />
    </div>

    <x-data-table title="Voucher Details">
        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Code</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Package</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Created</th>
        </x-slot:head>
        @forelse ($vouchers as $voucher)
            <tr>
                <td class="px-6 py-4"><code class="text-sm text-primary-600">{{ $voucher->code }}</code></td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $voucher->package?->name ?? 'N/A' }}</td>
                <td class="px-6 py-4"><span class="badge {{ $statusColors[$voucher->status] ?? 'badge-neutral' }}">{{ ucfirst($voucher->status) }}</span></td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $voucher->customer?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm text-text-muted">{{ $voucher->created_at?->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-text-muted">No vouchers found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $vouchers->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
