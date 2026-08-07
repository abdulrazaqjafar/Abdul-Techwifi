@props(['settings'])

@php
    use Illuminate\Support\Facades\Storage;
@endphp

<section class="hotspot-hero hotspot-animate-fade-in">
    <div class="hotspot-hero__mesh" aria-hidden="true"></div>
    <div class="hotspot-hero__network" aria-hidden="true">
        <svg viewBox="0 0 800 400" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <circle cx="120" cy="80" r="3" fill="#22c55e" opacity="0.6"/>
            <circle cx="320" cy="60" r="2" fill="#60a5fa" opacity="0.5"/>
            <circle cx="520" cy="100" r="3" fill="#22c55e" opacity="0.4"/>
            <circle cx="680" cy="70" r="2" fill="#a78bfa" opacity="0.5"/>
            <circle cx="200" cy="180" r="2.5" fill="#22c55e" opacity="0.45"/>
            <circle cx="400" cy="160" r="4" fill="#1e3a8a" opacity="0.35"/>
            <circle cx="600" cy="200" r="2.5" fill="#22c55e" opacity="0.5"/>
            <path d="M120 80 L320 60 L520 100 M320 60 L400 160 M520 100 L600 200 M200 180 L400 160" stroke="#22c55e" stroke-width="0.75" opacity="0.15"/>
            <path d="M400 160 L680 70" stroke="#1e3a8a" stroke-width="0.75" opacity="0.12"/>
        </svg>
    </div>

    <div class="hotspot-hero__content">
        @if ($settings->business_logo)
            <img src="{{ Storage::url($settings->business_logo) }}" alt="{{ $settings->business_name }}" class="hotspot-hero__logo mx-auto">
        @endif

        <p class="hotspot-hero__brand">{{ $settings->business_name }}</p>
        <p class="hotspot-hero__system">{{ __('hotspot.system_name') }}</p>

        <h1 class="hotspot-hero__title">{{ __('hotspot.hero_heading') }}</h1>
        <p class="hotspot-hero__desc">{{ __('hotspot.hero_description') }}</p>

        <div class="hotspot-hero__stats">
            <div class="hotspot-hero__stat">
                <svg class="h-5 w-5 text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 6.75 14.25 10.5 21 3.75M3.75 19.5h16.5" /></svg>
                <span>High Speed</span>
            </div>
            <div class="hotspot-hero__stat">
                <svg class="h-5 w-5 text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                <span>Secure</span>
            </div>
            <div class="hotspot-hero__stat">
                <svg class="h-5 w-5 text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <span>Instant</span>
            </div>
        </div>
    </div>
</section>
