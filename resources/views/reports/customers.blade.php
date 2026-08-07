<x-layouts.app title="Customer Report" subtitle="Customer activity and engagement overview">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Customer Report</h1>
                <p class="page-subtitle">Customer activity and engagement overview</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('reports.export.pdf', array_merge(['type' => 'customers'], $validated)) }}" variant="outline" size="sm">Export PDF</x-button>
                <x-button href="{{ route('reports.export.excel', ['type' => 'customers']) }}" variant="primary" size="sm">Export Excel</x-button>
            </div>
        </div>
    </x-slot:header>

    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-4 p-4">
        <div class="min-w-[240px] flex-1">
            <label class="label">Search</label>
            <input type="text" name="search" value="{{ $validated['search'] ?? '' }}" class="input-field" placeholder="Name, phone, email">
        </div>
        <x-button type="submit" variant="primary" size="sm">Search</x-button>
    </form>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card title="Total Customers" :value="number_format($summary['total_customers'])" icon="users" />
        <x-stat-card title="With Sales" :value="number_format($summary['with_sales'])" icon="users" color="primary" />
        <x-stat-card title="With Vouchers" :value="number_format($summary['with_vouchers'])" icon="users" color="blue" />
    </div>

    <x-data-table title="Customer List">
        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Phone</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Email</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Vouchers</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Sales</th>
        </x-slot:head>
        @forelse ($customers as $customer)
            <tr>
                <td class="px-6 py-4 font-medium dark:text-white">{{ $customer->name }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $customer->phone ?? '—' }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $customer->email ?? '—' }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $customer->vouchers_count }}</td>
                <td class="px-6 py-4 text-sm dark:text-slate-300">{{ $customer->sales_count }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-text-muted">No customers found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $customers->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
