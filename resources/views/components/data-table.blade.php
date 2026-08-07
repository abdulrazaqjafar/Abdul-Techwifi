@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @if ($title || isset($header))
        <div class="flex flex-col gap-4 border-b border-border px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700">
            <div>
                @if ($title)
                    <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-text-muted dark:text-slate-400">{{ $description }}</p>
                @endif
                @isset($header)
                    {{ $header }}
                @endisset
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    @isset($filters)
        <div class="border-b border-border bg-surface-muted px-6 py-4 dark:border-slate-700 dark:bg-slate-800/50">
            {{ $filters }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border dark:divide-slate-700">
            @isset($head)
                <thead class="bg-surface-muted dark:bg-slate-800/50">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-border bg-surface dark:divide-slate-700 dark:bg-slate-900">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-border px-6 py-4 dark:border-slate-700">
            {{ $footer }}
        </div>
    @endisset
</div>
