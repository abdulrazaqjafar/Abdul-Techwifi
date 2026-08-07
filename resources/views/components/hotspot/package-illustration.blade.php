@props(['name' => 'elephant'])

<div {{ $attributes->merge(['class' => 'hotspot-illustration hotspot-illustration--' . $name]) }} aria-hidden="true">
    @switch($name)
        @case('elephant')
            {{-- Tarangire — African Elephant --}}
            <svg class="hotspot-illustration__svg" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="savannaSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fde68a" stop-opacity="0.35"/><stop offset="100%" stop-color="#92400e" stop-opacity="0"/></linearGradient>
                    <linearGradient id="elephantBody" x1="80" y1="60" x2="280" y2="180" gradientUnits="userSpaceOnUse"><stop stop-color="#78716c"/><stop offset="0.5" stop-color="#57534e"/><stop offset="1" stop-color="#44403c"/></linearGradient>
                    <linearGradient id="elephantHighlight" x1="120" y1="80" x2="200" y2="140" gradientUnits="userSpaceOnUse"><stop stop-color="#a8a29e" stop-opacity="0.6"/><stop offset="1" stop-color="#78716c" stop-opacity="0"/></linearGradient>
                    <radialGradient id="sunGlow" cx="0.5" cy="0.5" r="0.5"><stop stop-color="#fbbf24" stop-opacity="0.5"/><stop offset="1" stop-color="#fbbf24" stop-opacity="0"/></radialGradient>
                </defs>
                <rect width="320" height="200" fill="url(#savannaSky)"/>
                <circle cx="260" cy="42" r="28" fill="url(#sunGlow)"/>
                <circle cx="260" cy="42" r="14" fill="#fbbf24" opacity="0.85"/>
                <ellipse cx="160" cy="178" rx="130" ry="18" fill="#78350f" opacity="0.25"/>
                <path d="M0 155 Q80 140 160 150 T320 148 L320 200 L0 200Z" fill="#92400e" opacity="0.3"/>
                <path d="M20 160 Q60 145 100 152 T180 148 T260 155" stroke="#a16207" stroke-width="2" opacity="0.4" fill="none"/>
                {{-- Elephant body --}}
                <ellipse cx="175" cy="130" rx="72" ry="48" fill="url(#elephantBody)"/>
                <ellipse cx="155" cy="115" rx="38" ry="32" fill="url(#elephantHighlight)"/>
                {{-- Head --}}
                <ellipse cx="95" cy="118" rx="42" ry="38" fill="url(#elephantBody)"/>
                {{-- Trunk --}}
                <path d="M58 125 C40 135 32 155 38 172 C42 182 52 178 55 165 C58 150 65 140 72 132" fill="#57534e" stroke="#44403c" stroke-width="1"/>
                {{-- Ear --}}
                <ellipse cx="108" cy="108" rx="28" ry="22" fill="#68625d" transform="rotate(-15 108 108)"/>
                {{-- Eye --}}
                <circle cx="82" cy="112" r="4" fill="#1c1917"/>
                <circle cx="83" cy="111" r="1.5" fill="#fafaf9"/>
                {{-- Legs --}}
                <rect x="118" y="158" width="18" height="32" rx="6" fill="#57534e"/>
                <rect x="148" y="158" width="18" height="32" rx="6" fill="#44403c"/>
                <rect x="188" y="158" width="18" height="32" rx="6" fill="#57534e"/>
                <rect x="218" y="158" width="18" height="32" rx="6" fill="#44403c"/>
                {{-- Tusks --}}
                <path d="M68 128 L62 145" stroke="#fef3c7" stroke-width="3" stroke-linecap="round"/>
                {{-- Acacia tree silhouette --}}
                <path d="M28 160 L28 120 M28 128 Q18 118 12 108 M28 125 Q38 112 44 100" stroke="#78350f" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
            </svg>
            @break

        @case('lion')
            {{-- Serengeti — African Lion --}}
            <svg class="hotspot-illustration__svg" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="lionSky" x1="0" y1="0" x2="320" y2="200" gradientUnits="userSpaceOnUse"><stop stop-color="#f97316" stop-opacity="0.3"/><stop offset="0.6" stop-color="#7c2d12" stop-opacity="0.15"/><stop offset="1" stop-color="#1e1b4b" stop-opacity="0.2"/></linearGradient>
                    <linearGradient id="lionMane" x1="120" y1="50" x2="200" y2="130" gradientUnits="userSpaceOnUse"><stop stop-color="#92400e"/><stop offset="1" stop-color="#451a03"/></linearGradient>
                    <linearGradient id="lionFur" x1="140" y1="90" x2="220" y2="160" gradientUnits="userSpaceOnUse"><stop stop-color="#d97706"/><stop offset="1" stop-color="#b45309"/></linearGradient>
                </defs>
                <rect width="320" height="200" fill="url(#lionSky)"/>
                <ellipse cx="160" cy="178" rx="130" ry="16" fill="#78350f" opacity="0.2"/>
                <path d="M0 158 Q100 145 200 152 T320 150 L320 200 L0 200Z" fill="#92400e" opacity="0.25"/>
                {{-- Mane --}}
                <circle cx="168" cy="108" r="52" fill="url(#lionMane)"/>
                <circle cx="148" cy="98" r="18" fill="#78350f" opacity="0.7"/>
                <circle cx="188" cy="96" r="16" fill="#78350f" opacity="0.7"/>
                <circle cx="168" cy="82" r="14" fill="#92400e" opacity="0.6"/>
                {{-- Face --}}
                <ellipse cx="168" cy="112" rx="32" ry="28" fill="url(#lionFur)"/>
                {{-- Ears --}}
                <ellipse cx="142" cy="92" rx="10" ry="12" fill="#b45309" transform="rotate(-20 142 92)"/>
                <ellipse cx="194" cy="92" rx="10" ry="12" fill="#b45309" transform="rotate(20 194 92)"/>
                {{-- Eyes --}}
                <ellipse cx="155" cy="108" rx="5" ry="6" fill="#1c1917"/>
                <ellipse cx="181" cy="108" rx="5" ry="6" fill="#1c1917"/>
                <circle cx="156" cy="107" r="1.5" fill="#fef3c7"/>
                <circle cx="182" cy="107" r="1.5" fill="#fef3c7"/>
                {{-- Nose --}}
                <path d="M162 122 Q168 128 174 122" stroke="#78350f" stroke-width="2" fill="none" stroke-linecap="round"/>
                <ellipse cx="168" cy="120" rx="6" ry="4" fill="#451a03"/>
                {{-- Body --}}
                <ellipse cx="200" cy="148" rx="55" ry="32" fill="url(#lionFur)"/>
                {{-- Paws --}}
                <ellipse cx="155" cy="168" rx="14" ry="10" fill="#b45309"/>
                <ellipse cx="230" cy="168" rx="14" ry="10" fill="#b45309"/>
                {{-- Tail --}}
                <path d="M248 140 Q270 120 278 100" stroke="#92400e" stroke-width="4" stroke-linecap="round" fill="none"/>
                <circle cx="278" cy="98" r="6" fill="#78350f"/>
            </svg>
            @break

        @case('giraffe')
            {{-- Ruaha — Giraffe --}}
            <svg class="hotspot-illustration__svg" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="giraffeSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.25"/><stop offset="100%" stop-color="#0c4a6e" stop-opacity="0.1"/></linearGradient>
                    <linearGradient id="giraffeBody" x1="100" y1="40" x2="220" y2="180" gradientUnits="userSpaceOnUse"><stop stop-color="#fde68a"/><stop offset="1" stop-color="#d97706"/></linearGradient>
                </defs>
                <rect width="320" height="200" fill="url(#giraffeSky)"/>
                <ellipse cx="160" cy="182" rx="120" ry="14" fill="#166534" opacity="0.2"/>
                <path d="M148 175 L148 70 Q148 55 158 48 L168 48 Q178 55 178 70 L178 175" fill="url(#giraffeBody)"/>
                <ellipse cx="163" cy="44" rx="22" ry="16" fill="url(#giraffeBody)"/>
                <ellipse cx="195" cy="158" rx="48" ry="30" fill="url(#giraffeBody)"/>
                <rect x="168" y="168" width="10" height="24" rx="4" fill="#d97706"/>
                <rect x="208" y="168" width="10" height="24" rx="4" fill="#b45309"/>
            </svg>
            @break

        @case('crater')
            {{-- Ngorongoro — Crater landscape --}}
            <svg class="hotspot-illustration__svg" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="craterSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#bae6fd"/><stop offset="100%" stop-color="#0e7490" stop-opacity="0.15"/></linearGradient>
                    <linearGradient id="craterRim" x1="60" y1="40" x2="260" y2="120" gradientUnits="userSpaceOnUse"><stop stop-color="#14b8a6"/><stop offset="1" stop-color="#0f766e"/></linearGradient>
                    <linearGradient id="craterFloor" x1="100" y1="100" x2="220" y2="170" gradientUnits="userSpaceOnUse"><stop stop-color="#86efac"/><stop offset="1" stop-color="#22c55e"/></linearGradient>
                </defs>
                <rect width="320" height="200" fill="url(#craterSky)"/>
                <ellipse cx="160" cy="130" rx="110" ry="45" fill="url(#craterRim)" opacity="0.85"/>
                <ellipse cx="160" cy="138" rx="78" ry="28" fill="url(#craterFloor)"/>
                <ellipse cx="160" cy="148" rx="52" ry="14" fill="#15803d" opacity="0.35"/>
                <circle cx="230" cy="42" r="18" fill="#fde68a" opacity="0.8"/>
                <path d="M148 175 a8 8 0 0 1 16 0M144 180 a14 14 0 0 1 24 0" stroke="#16a34a" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.7"/>
            </svg>
            @break

        @case('kilimanjaro')
            {{-- Kilimanjaro — Snow-capped mountain --}}
            <svg class="hotspot-illustration__svg" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="kiliSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#6366f1" stop-opacity="0.35"/><stop offset="50%" stop-color="#312e81" stop-opacity="0.2"/><stop offset="100%" stop-color="#0f172a" stop-opacity="0.1"/></linearGradient>
                    <linearGradient id="kiliPeak" x1="160" y1="20" x2="160" y2="100" gradientUnits="userSpaceOnUse"><stop stop-color="#f8fafc"/><stop offset="0.4" stop-color="#e2e8f0"/><stop offset="1" stop-color="#94a3b8"/></linearGradient>
                    <linearGradient id="kiliSlope" x1="80" y1="80" x2="240" y2="180" gradientUnits="userSpaceOnUse"><stop stop-color="#475569"/><stop offset="1" stop-color="#1e293b"/></linearGradient>
                    <linearGradient id="kiliForest" x1="0" y1="140" x2="320" y2="200" gradientUnits="userSpaceOnUse"><stop stop-color="#166534" stop-opacity="0.4"/><stop offset="1" stop-color="#14532d" stop-opacity="0.6"/></linearGradient>
                </defs>
                <rect width="320" height="200" fill="url(#kiliSky)"/>
                {{-- Stars --}}
                <circle cx="40" cy="30" r="1.2" fill="#fef3c7" opacity="0.8"/>
                <circle cx="90" cy="18" r="1" fill="#fef3c7" opacity="0.6"/>
                <circle cx="240" cy="25" r="1.2" fill="#fef3c7" opacity="0.7"/>
                <circle cx="280" cy="45" r="0.8" fill="#fef3c7" opacity="0.5"/>
                {{-- Distant mountains --}}
                <path d="M0 120 L60 80 L120 110 L180 70 L240 100 L320 75 L320 200 L0 200Z" fill="#334155" opacity="0.35"/>
                {{-- Main peak --}}
                <path d="M160 28 L280 165 L40 165Z" fill="url(#kiliSlope)"/>
                <path d="M160 28 L220 120 L100 120Z" fill="url(#kiliPeak)"/>
                {{-- Snow cap detail --}}
                <path d="M160 28 L185 75 L135 75Z" fill="#ffffff" opacity="0.9"/>
                <path d="M160 28 L200 90 L120 90Z" fill="#f1f5f9" opacity="0.5"/>
                {{-- Forest base --}}
                <path d="M0 155 Q80 140 160 148 T320 152 L320 200 L0 200Z" fill="url(#kiliForest)"/>
                {{-- Clouds --}}
                <ellipse cx="70" cy="95" rx="30" ry="12" fill="#f8fafc" opacity="0.15"/>
                <ellipse cx="250" cy="85" rx="35" ry="14" fill="#f8fafc" opacity="0.12"/>
                {{-- Wi-Fi signal overlay --}}
                <path d="M148 175 a8 8 0 0 1 16 0M144 180 a14 14 0 0 1 24 0M138 185 a22 22 0 0 1 36 0" stroke="#22c55e" stroke-width="2" stroke-linecap="round" fill="none" opacity="0.7"/>
                <circle cx="156" cy="178" r="2.5" fill="#16a34a"/>
            </svg>
            @break

        @default
            <svg class="hotspot-illustration__svg" viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="320" height="200" fill="url(#lionSky)"/>
                <path d="M148 175 a8 8 0 0 1 16 0M144 180 a14 14 0 0 1 24 0M138 185 a22 22 0 0 1 36 0" stroke="#22c55e" stroke-width="2" stroke-linecap="round" fill="none"/>
                <circle cx="156" cy="178" r="2.5" fill="#16a34a"/>
            </svg>
    @endswitch
</div>
