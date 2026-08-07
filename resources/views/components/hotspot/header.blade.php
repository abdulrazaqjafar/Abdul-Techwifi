@props(['settings'])

<header class="hotspot-topbar">
    <div class="hotspot-container flex items-center justify-between gap-4 py-4">
        <div class="flex items-center gap-3">
            <div class="hotspot-brand-mark" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M2.277 8.625c5.385-5.385 14.112-5.385 19.497 0M12 18.75h.008v.008H12v-.008Z" /></svg>
            </div>
            <div>
                <p class="hotspot-brand-name">{{ $settings->business_name }}</p>
                <p class="hotspot-brand-tag">Tech-Sphere Wi-Fi Billing</p>
            </div>
        </div>

        <form method="POST" action="{{ route('hotspot.locale') }}" class="hotspot-lang-switch">
            @csrf
            <button type="submit" name="locale" value="en" @class(['hotspot-lang-btn', 'hotspot-lang-btn--active' => app()->getLocale() === 'en'])">EN</button>
            <button type="submit" name="locale" value="sw" @class(['hotspot-lang-btn', 'hotspot-lang-btn--active' => app()->getLocale() === 'sw'])">SW</button>
        </form>
    </div>
</header>

<section class="hotspot-banner">
    <div class="hotspot-banner__pattern" aria-hidden="true"></div>
    <div class="hotspot-container relative py-10 text-center sm:py-12">
        <p class="hotspot-banner__eyebrow">{{ __('hotspot.welcome') }}</p>
        <h1 class="hotspot-banner__title">{{ __('hotspot.hero_heading') }}</h1>
        <p class="hotspot-banner__desc">{{ __('hotspot.hero_description') }}</p>
        <div class="hotspot-banner__pills">
            <span class="hotspot-banner__pill">{{ __('hotspot.pill_instant') }}</span>
            <span class="hotspot-banner__pill">{{ __('hotspot.pill_secure') }}</span>
            <span class="hotspot-banner__pill">{{ __('hotspot.pill_affordable') }}</span>
        </div>
    </div>
</section>
