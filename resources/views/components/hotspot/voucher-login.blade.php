<section class="hotspot-section" aria-labelledby="voucher-login-title">
    <div class="hotspot-section-head">
        <span class="hotspot-step-num">1</span>
        <div>
            <p class="hotspot-step-label">{{ __('hotspot.step_one') }}</p>
        </div>
    </div>

    <div class="hotspot-card hotspot-card--voucher mx-auto max-w-xl">
        <div class="hotspot-card__strip" aria-hidden="true"></div>
        <div class="hotspot-card__inner">
            <div class="hotspot-card__header">
                <div class="hotspot-card__icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75M4.5 9.75h15M4.5 6.75A2.25 2.25 0 0 0 2.25 9v6a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 19.5 15V9a2.25 2.25 0 0 0-2.25-2.25h-15Z" /></svg>
                </div>
                <div>
                    <h2 id="voucher-login-title" class="hotspot-card__title">{{ __('hotspot.have_voucher') }}</h2>
                    <p class="hotspot-card__subtitle">{{ __('hotspot.voucher_hint') }}</p>
                </div>
            </div>

            <div class="hotspot-card__body space-y-5">
                <div>
                    <label for="voucher_code" class="hotspot-field-label">{{ __('hotspot.voucher_code_label') }}</label>
                    <input
                        id="voucher_code"
                        type="text"
                        x-model="voucherCode"
                        @keydown.enter="loginVoucher()"
                        placeholder="WIFI-XXXX-XXXX"
                        class="hotspot-field hotspot-field--code font-mono uppercase tracking-widest"
                        autocomplete="off"
                        autocapitalize="characters"
                    >
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                    <input type="checkbox" :checked="rememberVoucher" @change="toggleRemember()" class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    {{ __('hotspot.remember_voucher') }}
                </label>

                <button type="button" @click="loginVoucher()" :disabled="loginLoading || !voucherCode.trim()" class="hotspot-btn hotspot-btn--block hotspot-btn--lg">
                    <svg x-show="loginLoading" x-cloak class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <svg x-show="!loginLoading" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M2.277 8.625c5.385-5.385 14.112-5.385 19.497 0M12 18.75h.008v.008H12v-.008Z" /></svg>
                    {{ __('hotspot.connect_now') }}
                </button>
            </div>
        </div>
    </div>
</section>
