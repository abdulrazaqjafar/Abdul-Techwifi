@props([
    'name' => 'modal',
    'title' => '',
    'maxWidth' => 'lg',
])

@php
    $maxWidths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
    ];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? open = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? open = false : null"
    x-on:keydown.escape.window="open = false"
    {{ $attributes }}
>
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-secondary-900/60 backdrop-blur-sm"
            @click="open = false"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full {{ $maxWidths[$maxWidth] ?? $maxWidths['lg'] }} rounded-2xl border border-border bg-surface shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            @click.stop
        >
            @if ($title)
                <div class="flex items-center justify-between border-b border-border px-6 py-4 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">{{ $title }}</h3>
                    <button type="button" @click="open = false" class="rounded-lg p-1 text-text-muted hover:bg-surface-muted dark:text-slate-400 dark:hover:bg-slate-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif
            <div class="p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
