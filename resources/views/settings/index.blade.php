<x-layouts.app title="Settings" subtitle="Configure business and router settings">
    <div x-data="{ tab: 'business' }">
        <div class="mb-6 flex flex-wrap gap-2 rounded-xl border border-border bg-surface p-1 dark:border-slate-700 dark:bg-slate-900">
            <button type="button" @click="tab = 'business'" :class="tab === 'business' ? 'bg-primary-600 text-white shadow-sm' : 'text-text-muted hover:text-text dark:text-slate-400'" class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition">
                Business Settings
            </button>
            <button type="button" @click="tab = 'offers'" :class="tab === 'offers' ? 'bg-primary-600 text-white shadow-sm' : 'text-text-muted hover:text-text dark:text-slate-400'" class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition">
                Offers & SMS
            </button>
            <button type="button" @click="tab = 'router'" :class="tab === 'router' ? 'bg-primary-600 text-white shadow-sm' : 'text-text-muted hover:text-text dark:text-slate-400'" class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition">
                Router Settings
            </button>
        </div>

        <div x-show="tab === 'business'" x-transition class="mx-auto max-w-2xl">
            <div class="card p-6">
                <h3 class="mb-6 text-lg font-semibold text-secondary-800 dark:text-white">Business Information</h3>
                <form method="POST" action="{{ route('settings.business.update') }}" enctype="multipart/form-data" class="space-y-6" @submit="$dispatch('form-submitting')">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="business_name" class="label">Business Name <span class="text-red-500">*</span></label>
                        <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $business->business_name) }}" required class="input-field">
                    </div>

                    <div>
                        <label for="business_logo" class="label">Business Logo</label>
                        @if ($business->business_logo)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $business->business_logo) }}" alt="Logo" class="h-16 rounded-lg border border-border object-contain p-1 dark:border-slate-700">
                            </div>
                        @endif
                        <input type="file" id="business_logo" name="business_logo" accept="image/*" class="input-field file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="phone" class="label">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $business->phone) }}" class="input-field">
                        </div>
                        <div>
                            <label for="currency" class="label">Currency <span class="text-red-500">*</span></label>
                            <input type="text" id="currency" name="currency" value="{{ old('currency', $business->currency) }}" required maxlength="3" class="input-field uppercase" placeholder="USD">
                        </div>
                    </div>

                    <div>
                        <label for="address" class="label">Address</label>
                        <textarea id="address" name="address" rows="2" class="input-field">{{ old('address', $business->address) }}</textarea>
                    </div>

                    <div>
                        <label for="timezone" class="label">Timezone <span class="text-red-500">*</span></label>
                        <select id="timezone" name="timezone" required class="input-field">
                            @foreach (timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $business->timezone) === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end border-t border-border pt-6 dark:border-slate-700">
                        <x-button type="submit" variant="primary">Save Business Settings</x-button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="tab === 'offers'" x-cloak x-transition class="mx-auto max-w-2xl">
            <div class="card p-6">
                <h3 class="mb-2 text-lg font-semibold text-secondary-800 dark:text-white">Monthly Offers & SMS</h3>
                <p class="mb-6 text-sm text-text-muted dark:text-slate-400">Offers hutumwa kiotomatiki siku ya pili ya mwisho wa kila mwezi (siku moja kabla ya mwisho wa mwezi).</p>

                <form method="POST" action="{{ route('settings.offers.update') }}" class="space-y-6" @submit="$dispatch('form-submitting')">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="monthly_offer_enabled" value="0">
                        <input type="checkbox" id="monthly_offer_enabled" name="monthly_offer_enabled" value="1" @checked(old('monthly_offer_enabled', $business->monthly_offer_enabled ?? true)) class="rounded border-border text-primary-600 focus:ring-primary-500">
                        <label for="monthly_offer_enabled" class="text-sm dark:text-slate-300">Washa offers za mwezi</label>
                    </div>

                    <div>
                        <label for="monthly_offer_discount_percent" class="label">Punguzo (%) <span class="text-red-500">*</span></label>
                        <input type="number" id="monthly_offer_discount_percent" name="monthly_offer_discount_percent" value="{{ old('monthly_offer_discount_percent', $business->monthly_offer_discount_percent ?? 10) }}" min="1" max="90" required class="input-field">
                    </div>

                    <div>
                        <label for="monthly_offer_message" class="label">Ujumbe wa SMS</label>
                        <textarea id="monthly_offer_message" name="monthly_offer_message" rows="4" class="input-field" placeholder="{{ $business->defaultOfferMessage() }}">{{ old('monthly_offer_message', $business->monthly_offer_message) }}</textarea>
                        <p class="mt-1 text-xs text-text-muted">Tumia: {name}, {business}, {discount}, {code}, {expires}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="sms_provider" class="label">SMS Provider</label>
                            <select id="sms_provider" name="sms_provider" class="input-field">
                                <option value="log" @selected(old('sms_provider', $business->sms_provider ?? 'log') === 'log')">Log (Test / Development)</option>
                                <option value="africastalking" @selected(old('sms_provider', $business->sms_provider) === 'africastalking')">Africa's Talking</option>
                            </select>
                        </div>
                        <div>
                            <label for="sms_sender_id" class="label">Sender ID</label>
                            <input type="text" id="sms_sender_id" name="sms_sender_id" value="{{ old('sms_sender_id', $business->sms_sender_id) }}" class="input-field" placeholder="TECHSPHERE">
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-border pt-6 dark:border-slate-700">
                        <x-button type="submit" variant="primary">Hifadhi Offers & SMS</x-button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="tab === 'router'" x-cloak x-transition class="mx-auto max-w-2xl">
            <div class="card p-6">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">MikroTik Router Configuration</h3>
                    @if ($router->is_connected)
                        <span class="badge badge-success">Connected</span>
                    @else
                        <span class="badge badge-danger">Disconnected</span>
                    @endif
                </div>

                </div>

                @include('router._settings-form')

                @if ($router->last_synced_at)
                    <p class="mt-4 text-sm text-text-muted dark:text-slate-400">Last synced: {{ $router->last_synced_at->format('M d, Y H:i') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
