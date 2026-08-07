<x-layouts.app title="Staff / Cashiers" subtitle="Sajili na simamia wafanyakazi wa voucher na mauzo ya cash">
    <x-slot:header>
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="page-title">Staff / Cashiers</h1>
                <p class="page-subtitle">Wafanyakazi wanaoweza kuuza voucher wakati hauko</p>
            </div>
            <div class="flex gap-2">
                <x-button href="{{ route('cashier.index') }}" variant="outline" size="sm">Fungua Cashier Panel</x-button>
                <x-button href="{{ route('staff.create') }}" variant="primary" size="sm">Sajili Mfanyakazi</x-button>
            </div>
        </div>
    </x-slot:header>

    <x-data-table>
        <x-slot:filters>
            <form method="GET" action="{{ route('staff.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="min-w-[240px] flex-1">
                    <label class="label">Tafuta</label>
                    <input type="text" name="search" value="{{ $search ?? '' }}" class="input-field" placeholder="Jina au email">
                </div>
                <x-button type="submit" variant="primary" size="sm">Tafuta</x-button>
                <x-button href="{{ route('staff.index') }}" variant="outline" size="sm">Safisha</x-button>
            </form>
        </x-slot:filters>

        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Jina</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Email</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Hali</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Mauzo</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Imesajiliwa</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted">Vitendo</th>
        </x-slot:head>

        @forelse ($staffMembers as $staff)
            <tr class="hover:bg-surface-muted/50 dark:hover:bg-slate-800/50">
                <td class="whitespace-nowrap px-6 py-4 font-medium text-secondary-800 dark:text-white">{{ $staff->name }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $staff->email }}</td>
                <td class="whitespace-nowrap px-6 py-4">
                    @if ($staff->is_active)
                        <span class="badge badge-success">Hai</span>
                    @else
                        <span class="badge badge-danger">Imesitishwa</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm dark:text-slate-300">{{ $staff->sales_count }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text-muted dark:text-slate-400">{{ $staff->created_at?->format('M d, Y') }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <x-button href="{{ route('staff.show', $staff) }}" variant="ghost" size="sm">Angalia</x-button>
                        <x-button href="{{ route('staff.edit', $staff) }}" variant="ghost" size="sm">Hariri</x-button>
                        <form method="POST" action="{{ route('staff.destroy', $staff) }}" x-ref="del{{ $staff->id }}" @submit.prevent="$dispatch('confirm-dialog', { title: 'Futa Mfanyakazi', message: 'Futa {{ $staff->name }}?', form: $refs.del{{ $staff->id }} })">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" size="sm">Futa</x-button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-sm text-text-muted">
                    Hakuna wafanyakazi bado. Bofya "Sajili Mfanyakazi" kuongeza mtu wa kusimamia voucher.
                </td>
            </tr>
        @endforelse

        <x-slot:footer>{{ $staffMembers->links() }}</x-slot:footer>
    </x-data-table>
</x-layouts.app>
