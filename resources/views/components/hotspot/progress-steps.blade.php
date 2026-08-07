@props(['currentStep' => 1])

@php
    $steps = [
        1 => __('hotspot.step_phone'),
        2 => __('hotspot.step_payment'),
        3 => __('hotspot.step_voucher'),
    ];
@endphp

<ol class="hotspot-progress" aria-label="Purchase progress">
    @foreach ($steps as $num => $label)
        <li @class([
            'hotspot-progress__step',
            'hotspot-progress__step--active' => $num === $currentStep,
            'hotspot-progress__step--done' => $num < $currentStep,
        ])>
            <span class="hotspot-progress__dot">
                @if ($num < $currentStep)
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                @else
                    {{ $num }}
                @endif
            </span>
            <span class="hotspot-progress__label">{{ $label }}</span>
        </li>
        @if (! $loop->last)
            <li @class(['hotspot-progress__line', 'hotspot-progress__line--done' => $num < $currentStep]) aria-hidden="true"></li>
        @endif
    @endforeach
</ol>
