<div x-show="successOpen" x-cloak class="hotspot-overlay">
    <div class="hotspot-overlay__bg"></div>
    <div class="hotspot-dialog hotspot-dialog--success text-center" role="dialog" aria-modal="true">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
            <svg class="h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        </div>

        <h3 class="text-2xl font-bold text-slate-800">{{ __('hotspot.payment_success') }}</h3>
        <p class="mt-1 text-sm text-primary-600">{{ __('hotspot.voucher_generated') }}</p>

        <template x-if="successData">
            <div class="my-6 rounded-xl border-2 border-dashed border-primary-300 bg-green-50 p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('hotspot.voucher_code') }}</p>
                <p class="mt-2 font-mono text-2xl font-bold tracking-widest text-primary-700" x-text="successData.code"></p>
                <div class="mt-4 grid grid-cols-2 gap-3 text-left text-sm">
                    <div>
                        <p class="text-xs text-slate-500">Package</p>
                        <p class="font-semibold text-slate-800" x-text="successData.package_name"></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('hotspot.expiry_date') }}</p>
                        <p class="font-semibold text-slate-800" x-text="formatExpiry(successData.expires_at)"></p>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-3">
            <button type="button" @click="connectAfterPurchase()" :disabled="loading" class="hotspot-btn hotspot-btn--block">
                {{ __('hotspot.connect_now') }}
            </button>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="copyVoucher()" class="hotspot-btn hotspot-btn--light">
                    <span x-text="copied ? '{{ __('hotspot.copied') }}' : '{{ __('hotspot.copy_voucher') }}'"></span>
                </button>
                <button type="button" @click="downloadReceipt()" class="hotspot-btn hotspot-btn--light">
                    {{ __('hotspot.download_receipt') }}
                </button>
            </div>
        </div>
    </div>
</div>
