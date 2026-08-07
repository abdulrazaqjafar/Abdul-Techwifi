@php $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'USD'; @endphp

<x-layouts.app title="{{ $package->name }}" subtitle="Package details">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="card p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-secondary-800 dark:text-white">{{ $package->name }}</h2>
                    @if ($package->description)
                        <p class="mt-2 text-sm text-text-muted dark:text-slate-400">{{ $package->description }}</p>
                    @endif
                </div>
                @if ($package->is_active)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-neutral">Inactive</span>
                @endif
            </div>

            <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-surface-muted p-4 dark:bg-slate-800"><dt class="text-xs font-semibold uppercase text-text-muted">Duration</dt><dd class="mt-1 font-semibold dark:text-white">{{ $package->getDurationLabel() }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-4 dark:bg-slate-800"><dt class="text-xs font-semibold uppercase text-text-muted">Price</dt><dd class="mt-1 font-semibold text-primary-600">{{ number_format($package->price, 2) }} {{ $currency }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-4 dark:bg-slate-800"><dt class="text-xs font-semibold uppercase text-text-muted">MikroTik Profile</dt><dd class="mt-1 font-semibold dark:text-white">{{ $package->profile_name }}</dd></div>
                <div class="rounded-xl bg-surface-muted p-4 dark:bg-slate-800"><dt class="text-xs font-semibold uppercase text-text-muted">Vouchers / Sales</dt><dd class="mt-1 font-semibold dark:text-white">{{ $package->vouchers_count }} / {{ $package->sales_count }}</dd></div>
            </dl>

            <div class="mt-6 flex gap-3">
                <x-button href="{{ route('packages.edit', $package) }}" variant="primary">Edit</x-button>
                <x-button href="{{ route('packages.index') }}" variant="outline">Back</x-button>
            </div>
        </div>
    </div>
</x-layouts.app>
