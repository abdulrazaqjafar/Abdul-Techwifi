@php
    $packageBadges = config('hotspot.package_badges', []);
    $packageTiers = config('hotspot.package_tiers', []);
    $packageImages = config('hotspot.package_images', []);
    $featuredPackage = config('hotspot.featured_package');

    $badgeKeys = [
        'Quick Access' => 'quick_access',
        'Most Popular' => 'most_popular',
        'Best Value' => 'best_value',
        'Smart Choice' => 'smart_choice',
        'Premium' => 'premium',
    ];
@endphp

@push('hotspot-config')
<script>window.__HOTSPOT__ = @json($hotspotConfig);</script>
@endpush

<x-layouts.hotspot :settings="$settings">
    <div
        x-show="autoConnecting"
        x-cloak
        class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/80 px-6 backdrop-blur-sm"
    >
        <div class="max-w-sm rounded-2xl bg-white p-8 text-center shadow-2xl dark:bg-slate-900">
            <svg class="mx-auto mb-4 h-10 w-10 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-lg font-semibold text-slate-800 dark:text-white">{{ __('hotspot.auto_reconnect') }}</p>
        </div>
    </div>

    <canvas id="confetti-canvas" class="pointer-events-none fixed inset-0 z-[90]" x-show="confettiActive"></canvas>

    <div class="hotspot-page min-h-screen">
        <x-hotspot.header :settings="$settings" />

        @if ($isLivePayments ?? false)
            <div class="border-b border-green-500/30 bg-green-500/10 px-4 py-2 text-center text-sm text-green-100">
                {{ __('hotspot.no_data_banner') }} {{ __('hotspot.payment_wifi_hint') }}
            </div>
        @elseif ($isPaymentSandbox ?? false)
            <div class="border-b border-amber-500/30 bg-amber-500/10 px-4 py-2 text-center text-sm text-amber-200">
                @if ($usingSimulatedFallback ?? false)
                    Hali ya majaribio — malipo yanasimuliwa (weka ClickPesa credentials kwenye .env kwa sandbox halisi)
                @else
                    Hali ya majaribio — ClickPesa Sandbox (hakuna pesa halisi)
                @endif
            </div>
        @endif

        <main class="hotspot-main">
            <div class="hotspot-container pb-12 pt-8 sm:pb-16 sm:pt-10">
                <x-hotspot.voucher-login />

                <section id="packages-section" class="hotspot-section mt-14 scroll-mt-6 sm:mt-20 sm:scroll-mt-8" aria-labelledby="packages-title">
                    <div class="hotspot-section-head hotspot-section-head--center mb-10">
                        <span class="hotspot-step-num">2</span>
                        <div>
                            <p class="hotspot-step-label">{{ __('hotspot.step_two') }}</p>
                            <h2 id="packages-title" class="hotspot-section-title">{{ __('hotspot.packages_title') }}</h2>
                            <p class="hotspot-section-desc">{{ __('hotspot.packages_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="hotspot-plans-grid">
                        @forelse ($packages as $package)
                            @php
                                $badge = $packageBadges[$package->name] ?? 'Standard';
                                $badgeKey = $badgeKeys[$badge] ?? null;
                            @endphp
                        <x-hotspot.package-card
                            :package="$package"
                            :icon="$packageIcons[$package->name] ?? 'wifi'"
                            :image="$packageImages[$package->name] ?? null"
                            :badge="$badge"
                            :badge-key="$badgeKey"
                            :tier="$packageTiers[$package->name] ?? null"
                            :currency="$settings->currency ?? 'TSh'"
                            :featured="$package->name === $featuredPackage"
                            :payable="! isset($paymentMinAmount) || $package->price >= $paymentMinAmount"
                            :min-amount="$paymentMinAmount"
                        />
                        @empty
                            <div class="col-span-full hotspot-card py-12 text-center text-slate-500">{{ __('hotspot.loading') }}</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>

        <x-hotspot.footer :settings="$settings" />
    </div>

    <x-hotspot.purchase-modal :settings="$settings" :providers="$providers" />
    <x-hotspot.success-modal />
</x-layouts.hotspot>
