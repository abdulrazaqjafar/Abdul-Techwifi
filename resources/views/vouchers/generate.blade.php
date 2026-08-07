@php
    $packages = $packages ?? \App\Models\Package::query()->where('is_active', true)->orderBy('name')->get();
    $customers = $customers ?? \App\Models\Customer::query()->orderBy('name')->get();
@endphp

<x-layouts.app title="Generate Vouchers" subtitle="Create single or bulk Wi-Fi vouchers">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('vouchers.generate') }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf

                <div>
                    <label for="package_id" class="label">Package <span class="text-red-500">*</span></label>
                    <select id="package_id" name="package_id" required class="input-field">
                        <option value="">Select a package</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected(old('package_id') == $package->id)>
                                {{ $package->name }} — {{ $package->getDurationLabel() }} — {{ number_format($package->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="customer_id" class="label">Customer (Optional)</label>
                    <select id="customer_id" name="customer_id" class="input-field">
                        <option value="">No customer assigned</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} @if($customer->phone)({{ $customer->phone }})@endif</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('vouchers.index') }}" variant="outline">Cancel</x-button>
                    <x-button type="submit" variant="primary">Generate Single Voucher</x-button>
                </div>
            </form>
        </div>

        <div class="card mt-6 p-6" x-data="{ count: 100 }">
            <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Bulk Generation</h3>
            <p class="mb-4 text-sm text-text-muted dark:text-slate-400">Generate multiple vouchers at once (max 500 per batch).</p>

            <form method="POST" action="{{ route('vouchers.generate-bulk') }}" class="space-y-4" @submit="$dispatch('form-submitting')">
                @csrf

                <div>
                    <label for="bulk_package_id" class="label">Package <span class="text-red-500">*</span></label>
                    <select id="bulk_package_id" name="package_id" required class="input-field">
                        <option value="">Select a package</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="count" class="label">Quantity <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ([100, 200, 500, 1000] as $qty)
                            <button type="button" @click="count = {{ min($qty, 500) }}" :class="count === {{ min($qty, 500) }} ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'border-border dark:border-slate-700'" class="rounded-xl border px-4 py-2 text-sm font-medium transition">{{ $qty }}</button>
                        @endforeach
                    </div>
                    <input type="number" id="count" name="count" x-model.number="count" min="1" max="500" required class="input-field mt-2" placeholder="Custom count (max 500)">
                </div>

                <div>
                    <label for="bulk_customer_id" class="label">Customer (Optional)</label>
                    <select id="bulk_customer_id" name="customer_id" class="input-field">
                        <option value="">No customer assigned</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <x-button type="submit" variant="secondary" class="w-full">Generate Bulk Vouchers</x-button>
            </form>
        </div>
    </div>
</x-layouts.app>
