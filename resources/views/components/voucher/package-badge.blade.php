@props(['name' => 'wifi', 'packageName' => null])

@php
    $iconName = $name;
    if ($packageName) {
        $iconName = config('hotspot.package_icons')[$packageName] ?? $name;
    }
@endphp

<div {{ $attributes->merge(['class' => 'voucher-package-badge voucher-package-badge--' . $iconName]) }}>
    @switch($iconName)
        @case('clock')
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <circle cx="16" cy="17" r="9" stroke="currentColor" stroke-width="2"/>
                <path d="M16 11v6l4.5 2.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            @break
        @case('sun')
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <circle cx="16" cy="15" r="6" fill="currentColor" opacity="0.25"/>
                <circle cx="16" cy="15" r="6" stroke="currentColor" stroke-width="2"/>
                <path d="M16 4v3M16 23v3M4 15h3M25 15h3M7.5 7.5l2 2M22.5 22.5l2 2M7.5 22.5l2-2M22.5 9.5l2-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            @break
        @case('calendar')
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect x="5" y="8" width="22" height="18" rx="3" stroke="currentColor" stroke-width="2"/>
                <path d="M5 13h22M11 5v5M21 5v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="11" cy="18" r="1.5" fill="currentColor"/><circle cx="16" cy="18" r="1.5" fill="currentColor"/><circle cx="21" cy="18" r="1.5" fill="currentColor"/>
            </svg>
            @break
        @case('unlimited')
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <path d="M5 24l8-14 5 8 9-12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 22c4 2 8 2 12 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" opacity="0.7"/>
            </svg>
            @break
        @default
            <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <path d="M7 20c4-4 10-4 14 0M4 15c6-6 14-6 20 0M10 25a4 4 0 0 1 12 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
    @endswitch
</div>
