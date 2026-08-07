<x-layouts.app title="Customers" subtitle="Manage your Wi-Fi customers">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Customers</h1>
                <p class="page-subtitle">Manage your Wi-Fi customers</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('customers.create') }}" variant="primary" size="sm">Add Customer</x-button>
                <x-button href="{{ route('customers.export', request()->query()) }}" variant="outline" size="sm">Export</x-button>
            </div>
        </div>
    </x-slot:header>

    <x-data-table>
        <x-slot:filters>
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="min-w-[240px] flex-1">
                    <label class="label">Search</label>
                    <input type="text" name="search" value="{{ $search ?? '' }}" class="input-field" placeholder="Name, phone, or email">
                </div>
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
                <x-button href="{{ route('customers.index') }}" variant="outline" size="sm">Reset</x-button>
            </form>
        </x-slot:filters>

        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Phone</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Email</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Vouchers</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Sales</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">Actions</th>
        </x-slot:head>

        @forelse ($customers as $customer)
            <tr class="hover:bg-surface-muted/50 dark:hover:bg-slate-800/50">
                <td class="whitespace-nowrap px-6 py-4">
                    <div class="font-medium text-secondary-800 dark:text-white">{{ $customer->name }}</div>
                    @if ($customer->notes)
                        <div class="mt-0.5 max-w-xs truncate text-xs text-text-muted">{{ $customer->notes }}</div>
                    @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $customer->phone ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $customer->email ?? '—' }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $customer->vouchers_count }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $customer->sales_count }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <x-button href="{{ route('customers.show', $customer) }}" variant="ghost" size="sm">View</x-button>
                        <x-button href="{{ route('customers.edit', $customer) }}" variant="ghost" size="sm">Edit</x-button>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" x-ref="del{{ $customer->id }}" @submit.prevent="$dispatch('confirm-dialog', { title: 'Delete Customer', message: 'Delete {{ $customer->name }}?', form: $refs.del{{ $customer->id }} })">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-sm text-text-muted">No customers found.</td>
            </tr>
        @endforelse

        <x-slot:footer>{{ $customers->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
