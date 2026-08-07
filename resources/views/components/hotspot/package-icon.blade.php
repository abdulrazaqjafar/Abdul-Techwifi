@props(['name' => 'wifi', 'size' => 'md'])

<div {{ $attributes->merge(['class' => 'hotspot-package-icon hotspot-package-icon--' . $name . ' hotspot-package-icon--' . $size]) }} aria-hidden="true">
    @switch($name)
        @case('clock')
            <svg class="hotspot-package-icon__svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="24" cy="26" r="14" stroke="currentColor" stroke-width="2.5" opacity="0.35"/>
                <circle cx="24" cy="26" r="14" stroke="url(#clockGrad)" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="55 33"/>
                <path d="M24 16v10l7 4" stroke="url(#clockGrad)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20 8h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
                <circle cx="24" cy="8" r="2" fill="currentColor" opacity="0.6"/>
                <defs>
                    <linearGradient id="clockGrad" x1="10" y1="12" x2="38" y2="40" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#6ee7b7"/>
                        <stop offset="1" stop-color="#10b981"/>
                    </linearGradient>
                </defs>
            </svg>
            @break

        @case('sun')
            <svg class="hotspot-package-icon__svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 34h32" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.25"/>
                <path d="M6 34c4-3 8-4 12-4s8 1 12 4" stroke="url(#sunGrad)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="24" cy="22" r="9" fill="url(#sunFill)" stroke="url(#sunGrad)" stroke-width="2"/>
                <path d="M24 4v4M24 36v4M4 22h4M40 22h4M10.3 10.3l2.8 2.8M34.9 34.9l2.8 2.8M10.3 33.7l2.8-2.8M34.9 11.1l2.8-2.8" stroke="url(#sunGrad)" stroke-width="2" stroke-linecap="round"/>
                <defs>
                    <linearGradient id="sunGrad" x1="8" y1="8" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#fde68a"/>
                        <stop offset="1" stop-color="#f59e0b"/>
                    </linearGradient>
                    <radialGradient id="sunFill" cx="0" cy="0" r="1" gradientTransform="translate(20 18) rotate(45) scale(14)" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#fef3c7"/>
                        <stop offset="1" stop-color="#fbbf24"/>
                    </radialGradient>
                </defs>
            </svg>
            @break

        @case('calendar')
            <svg class="hotspot-package-icon__svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="8" y="12" width="32" height="28" rx="4" stroke="url(#calGrad)" stroke-width="2.5"/>
                <path d="M8 20h32" stroke="url(#calGrad)" stroke-width="2"/>
                <path d="M16 8v6M32 8v6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
                <circle cx="16" cy="28" r="2" fill="#38bdf8"/>
                <circle cx="24" cy="28" r="2" fill="#0ea5e9"/>
                <circle cx="32" cy="28" r="2" fill="#38bdf8"/>
                <circle cx="16" cy="34" r="2" fill="#0ea5e9" opacity="0.7"/>
                <circle cx="24" cy="34" r="2" fill="#38bdf8" opacity="0.7"/>
                <circle cx="32" cy="34" r="2" fill="#0ea5e9" opacity="0.7"/>
                <defs>
                    <linearGradient id="calGrad" x1="8" y1="12" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#7dd3fc"/>
                        <stop offset="1" stop-color="#0284c7"/>
                    </linearGradient>
                </defs>
            </svg>
            @break

        @case('unlimited')
            <svg class="hotspot-package-icon__svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 36l12-20 8 12 12-16" stroke="url(#peakGrad)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M28 12l4-4 8 8-4 4" fill="url(#peakGrad)" opacity="0.85"/>
                <path d="M10 38c6-2 10-2 14 0s10 2 14 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.2"/>
                <path d="M14 30c4 2 8 2 12 0s8-2 12 0" stroke="url(#wifiGrad)" stroke-width="2" stroke-linecap="round"/>
                <circle cx="24" cy="30" r="1.5" fill="#c4b5fd"/>
                <defs>
                    <linearGradient id="peakGrad" x1="8" y1="8" x2="40" y2="38" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#c4b5fd"/>
                        <stop offset="1" stop-color="#7c3aed"/>
                    </linearGradient>
                    <linearGradient id="wifiGrad" x1="14" y1="30" x2="38" y2="30" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#a78bfa"/>
                        <stop offset="1" stop-color="#8b5cf6"/>
                    </linearGradient>
                </defs>
            </svg>
            @break

        @default
            <svg class="hotspot-package-icon__svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 30c5-5 13-5 18 0M6 24c8-8 20-8 28 0M14 36a6 6 0 0 1 12 0" stroke="url(#wifiGradDefault)" stroke-width="2.5" stroke-linecap="round"/>
                <circle cx="24" cy="36" r="2" fill="#22c55e"/>
                <defs>
                    <linearGradient id="wifiGradDefault" x1="6" y1="18" x2="42" y2="36" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#86efac"/>
                        <stop offset="1" stop-color="#16a34a"/>
                    </linearGradient>
                </defs>
            </svg>
    @endswitch
</div>
