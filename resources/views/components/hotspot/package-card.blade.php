@props([
    'package',
    'badge' => 'Standard',
    'badgeKey' => null,
    'tier' => null,
    'currency' => 'TSh',
    'featured' => false,
    'icon' => 'wifi',
    'image' => null,
    'payable' => true,
    'minAmount' => null,
])

@php
    $badgeLabel = $badgeKey ? __("hotspot.{$badgeKey}") : $badge;
    $tierLabel = $tier ?? $package->getDurationLabel();
    $dailyRate = $package->getDailyRate();
    $imageUrl = $image ? asset($image) : null;
    $imageFocus = config('hotspot.package_image_focus.'.$package->name, 'center');
    $accentMap = [
        'Serengeti Daily' => 'amber',
        'Ruaha Weekly' => 'blue',
        'Ngorongoro Fortnight' => 'teal',
        'Kilimanjaro Unlimited' => 'violet',
    ];
    $accent = $accentMap[$package->name] ?? 'green';
@endphp

<article @class([
    'hotspot-plan hotspot-plan--' . $accent,
    'hotspot-plan--featured' => $featured,
    'hotspot-plan--disabled' => ! $payable,
])>
    <div class="hotspot-plan__accent" aria-hidden="true"></div>

    <div class="hotspot-plan__visual">
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $package->name }}"
                class="hotspot-plan__photo"
                style="object-position: {{ $imageFocus }}"
                loading="lazy"
                decoding="async"
            >
        @endif
        <div class="hotspot-plan__photo-overlay" aria-hidden="true"></div>
        <span class="hotspot-plan__photo-tier">{{ $tierLabel }}</span>
    </div>

    @if ($featured)
        <div class="hotspot-plan__ribbon">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/></svg>
            {{ $badgeLabel }}
        </div>
    @else
        <span class="hotspot-plan__tag">{{ $badgeLabel }}</span>
    @endif

    <div class="hotspot-plan__body">
        <h3 class="hotspot-plan__name">{{ $package->name }}</h3>

        <div class="hotspot-plan__price-wrap">
            <div class="hotspot-plan__price">
                <span class="hotspot-plan__currency">{{ $currency }}</span>
                {{ number_format($package->price, 0) }}
            </div>
            <p class="hotspot-plan__price-note">{{ __('hotspot.per_package') }}</p>
            @if ($dailyRate && $package->duration_days > 1)
                <p class="hotspot-plan__daily-rate">
                    {{ __('hotspot.daily_rate', ['amount' => number_format($dailyRate), 'currency' => $currency]) }}
                </p>
            @endif
        </div>

        <ul class="hotspot-plan__features">
            <li>
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <span><strong>{{ $package->getDurationLabel() }}</strong> {{ __('hotspot.access') }}</span>
            </li>
            <li>
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M2.277 8.625c5.385-5.385 14.112-5.385 19.497 0M12 18.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ __('hotspot.high_speed') }}</span>
            </li>
            <li>
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                <span>{{ __('hotspot.secure_wifi') }}</span>
            </li>
        </ul>

        <button
            type="button"
            data-package-id="{{ $package->id }}"
            data-package-name="{{ $package->name }}"
            data-package-price="{{ $package->price }}"
            data-package-duration="{{ $package->getDurationLabel() }}"
            data-package-icon="{{ $icon }}"
            @if ($payable)
                @click="openPurchaseFromCard($el)"
            @endif
            @class([
                'hotspot-btn hotspot-btn--block hotspot-plan__btn',
                'opacity-50 cursor-not-allowed' => ! $payable,
            ])
            @disabled(! $payable)
        >
            @if ($payable)
                {{ __('hotspot.buy_now') }}
            @else
                {{ __('hotspot.minimum_amount', ['min' => number_format($minAmount ?? 908)]) }}
            @endif
        </button>
    </div>
</article>
