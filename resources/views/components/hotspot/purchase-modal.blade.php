<div x-show="modalOpen" x-cloak class="hotspot-overlay" @keydown.escape.window="closeModal()">
    <div class="hotspot-overlay__bg" @click="closeModal()"></div>
    <div class="hotspot-dialog" @click.stop role="dialog" aria-modal="true">
        <button type="button" @click="closeModal()" class="hotspot-dialog__close" aria-label="Close">&times;</button>

        <template x-if="selectedPackage">
            <div>
                <h3 class="hotspot-dialog__title">{{ __('hotspot.buy_now') }}</h3>

                <ol class="hotspot-steps mb-6">
                    <li class="hotspot-steps__item" :class="modalStep === 'phone' ? 'is-active' : 'is-done'">
                        <span class="hotspot-steps__num">1</span>
                        <span>{{ __('hotspot.step_phone') }}</span>
                    </li>
                    <li class="hotspot-steps__item" :class="modalStep === 'payment' ? 'is-active' : ''">
                        <span class="hotspot-steps__num">2</span>
                        <span>{{ __('hotspot.step_payment') }}</span>
                    </li>
                    <li class="hotspot-steps__item">
                        <span class="hotspot-steps__num">3</span>
                        <span>{{ __('hotspot.step_voucher') }}</span>
                    </li>
                </ol>

                <div class="hotspot-dialog__package mb-5 rounded-xl bg-slate-50 p-4 text-center">
                    <p class="text-lg font-bold text-slate-800" x-text="selectedPackage.name"></p>
                    <p class="text-sm text-slate-500" x-text="selectedPackage.duration"></p>
                    <p class="mt-2 text-2xl font-extrabold text-primary-600">
                        {{ $settings->currency ?? 'TSh' }} <span x-text="Number(selectedPackage.price).toLocaleString()"></span>
                    </p>
                </div>

                <div x-show="modalStep === 'phone'" class="space-y-4">
                    <p class="rounded-lg bg-green-50 px-3 py-2 text-xs text-green-800">{{ __('hotspot.phone_hint') }}</p>
                    <div>
                        <label class="hotspot-field-label">{{ __('hotspot.phone_title') }}</label>
                        <input type="tel" x-model="phone" placeholder="{{ __('hotspot.phone_placeholder') }}" class="hotspot-field" inputmode="tel" autocomplete="tel">
                    </div>
                    @if (count($providers) > 1)
                        <div>
                            <label class="hotspot-field-label">{{ __('hotspot.select_provider') }}</label>
                            <select x-model="provider" class="hotspot-field">
                                @foreach ($providers as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <button type="button" @click="submitPhone()" :disabled="loading || !phone.trim()" class="hotspot-btn hotspot-btn--block">
                        <span x-show="!loading">{{ __('hotspot.continue') }}</span>
                        <span x-show="loading" x-cloak>{{ __('hotspot.waiting_payment') }}</span>
                    </button>
                </div>

                <div x-show="modalStep === 'payment'" x-cloak class="py-8 text-center">
                    <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-primary-600"></div>
                    <p class="font-semibold text-slate-800" x-text="paymentMessage"></p>
                    <p class="mt-2 text-sm text-slate-500">{{ __('hotspot.payment_prompt') }}</p>
                    <p class="mt-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-800">{{ __('hotspot.payment_wifi_hint') }}</p>
                </div>
            </div>
        </template>
    </div>
</div>
