@props(['settings'])

@php
    $supportEmail = config('hotspot.support.email');
@endphp

<footer class="hotspot-footer">
    <div class="hotspot-container py-10">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
                <p class="hotspot-footer__label">{{ __('hotspot.support') }}</p>
                <a href="tel:{{ preg_replace('/\s+/', '', $settings->phone ?? '+255679014566') }}" class="hotspot-footer__value">{{ $settings->phone ?? '+255679014566' }}</a>
            </div>
            <div>
                <p class="hotspot-footer__label">{{ __('hotspot.whatsapp') }}</p>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings->whatsapp ?? '255679014566') }}" target="_blank" rel="noopener" class="hotspot-footer__value">{{ $settings->whatsapp ?? '+255679014566' }}</a>
            </div>
            <div>
                <p class="hotspot-footer__label">{{ __('hotspot.email') }}</p>
                <a href="mailto:{{ $supportEmail }}" class="hotspot-footer__value">{{ $supportEmail }}</a>
            </div>
        </div>

        <div class="mt-8 border-t border-slate-700 pt-6 text-center text-xs text-slate-400">
            <p>&copy; {{ date('Y') }} {{ $settings->business_name }}. {{ __('hotspot.copyright') }}</p>
            <p class="mt-2">{{ __('hotspot.powered_by') }}</p>
        </div>
    </div>
</footer>
