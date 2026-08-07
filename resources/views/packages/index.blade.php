@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD'; @endphp

<x-layouts.app title="Packages" subtitle="Manage Wi-Fi hotspot packages and pricing">
    <x-slot:header>
        <div class="flex w-full items-center justify-between">
            <div>
                <h1 class="page-title">Packages</h1>
                <p class="page-subtitle">Manage Wi-Fi hotspot packages and pricing</p>
            </div>
            <x-button href="{{ route('packages.create') }}" variant="primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Package
            </x-button>
        </div>
    </x-slot:header>

    <x-data-table title="All Packages" description="{{ $packages->total() }} packages found">
        <x-slot:head>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Duration</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Price</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Profile</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Vouchers</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Sales</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Status</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-text-muted dark:text-slate-400">Actions</th>
        </x-slot:head>

        @forelse ($packages as $package)
            <tr class="hover:bg-surface-muted/50 dark:hover:bg-slate-800/50">
                <td class="whitespace-nowrap px-6 py-4">
                    <div class="font-medium text-secondary-800 dark:text-white">{{ $package->name }}</div>
                    @if ($package->description)
                        <div class="mt-0.5 max-w-xs truncate text-xs text-text-muted dark:text-slate-400">{{ $package->description }}</div>
                    @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text dark:text-slate-300">{{ $package->getDurationLabel() }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-primary-600 dark:text-primary-400">{{ number_format($package->price, 2) }} {{ $currency }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text dark:text-slate-300">{{ $package->profile_name }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text dark:text-slate-300">{{ $package->vouchers_count }}</td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-text dark:text-slate-300">{{ $package->sales_count }}</td>
                <td class="whitespace-nowrap px-6 py-4">
                    @if ($package->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-neutral">Inactive</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <x-button href="{{ route('packages.edit', $package) }}" variant="ghost" size="sm">Edit</x-button>
                        <form
                            method="POST"
                            action="{{ route('packages.destroy', $package) }}"
                            x-ref="deleteForm{{ $package->id }}"
                            @submit.prevent="$dispatch('confirm-dialog', {
                                title: 'Delete Package',
                                message: 'Are you sure you want to delete {{ $package->name }}? This action cannot be undone.',
                                form: $refs.deleteForm{{ $package->id }}
                            })"
                        >
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" size="sm">Delete</x-button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-sm text-text-muted dark:text-slate-400">
                    No packages found. <a href="{{ route('packages.create') }}" class="font-medium text-primary-600 hover:underline">Create your first package</a>
                </td>
            </tr>
        @endforelse

        <x-slot:footer>
            {{ $packages->links() }}
        </x-slot:footer>
    </x-data-table>
</x-layouts.app>
