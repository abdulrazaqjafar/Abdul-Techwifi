@php $currency = $settings->currency ?? 'TZS'; @endphp

<x-layouts.app title="Uza Voucher" subtitle="Tengeneza voucher na rekodi mauzo ya cash kwa hatua moja">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('cashier.sell.store') }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                @csrf

                <div>
                    <label for="package_id" class="label">Package <span class="text-red-500">*</span></label>
                    <select id="package_id" name="package_id" required class="input-field">
                        <option value="">Chagua package</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" data-price="{{ $package->price }}" @selected(old('package_id') == $package->id)>
                                {{ $package->name }} — {{ $package->getDurationLabel() }} — {{ number_format($package->price, 0) }} {{ $currency }}
                            </option>
                        @endforeach
                    </select>
                    @error('package_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_name" class="label">Jina la Mteja (Hiari)</label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="input-field" placeholder="Mfano: Juma Hassan">
                </div>

                <div>
                    <label for="phone" class="label">Simu (Hiari)</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="input-field" placeholder="0712345678">
                </div>

                <div>
                    <label for="amount" class="label">Kiasi (Cash)</label>
                    <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="908" step="1" class="input-field" placeholder="Acha tupu kutumia bei ya package">
                    <p class="mt-1 text-xs text-text-muted dark:text-slate-400">Kwa kawaida tumia bei ya package. Badilisha tu ikiwa kuna makubaliano maalum.</p>
                </div>

                <div>
                    <label for="notes" class="label">Maelezo (Hiari)</label>
                    <textarea id="notes" name="notes" rows="2" class="input-field" placeholder="Maelezo ya ziada...">{{ old('notes') }}</textarea>
                </div>

                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-900/20">
                    <p class="text-sm font-medium text-primary-800 dark:text-primary-200">Malipo: Cash tu</p>
                    <p class="mt-1 text-xs text-primary-700 dark:text-primary-300">Voucher itatengenezwa na mauzo yatarekodiwa mara moja.</p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-6 dark:border-slate-700">
                    <x-button href="{{ route('cashier.index') }}" variant="outline">Ghairi</x-button>
                    <x-button type="submit" variant="primary">Tengeneza & Uza</x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
