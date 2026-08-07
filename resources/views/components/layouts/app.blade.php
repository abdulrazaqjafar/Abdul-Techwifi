<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="darkMode()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - Tech-Sphere Wi-fi Billing System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body
    x-data="{ sidebarOpen: false, loading: false }"
    x-on:form-submitting.window="loading = true"
    class="min-h-screen"
>
    <div class="flex min-h-screen">
        <x-sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-surface/80 px-4 backdrop-blur-md sm:px-6 dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="sidebarOpen = !sidebarOpen"
                        class="rounded-xl p-2 text-text-muted hover:bg-surface-muted lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    @isset($header)
                        {{ $header }}
                    @else
                        <div>
                            <h1 class="page-title">{{ $title ?? 'Dashboard' }}</h1>
                            @isset($subtitle)
                                <p class="page-subtitle">{{ $subtitle }}</p>
                            @endisset
                        </div>
                    @endisset
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="toggle()"
                        class="rounded-xl p-2 text-text-muted transition hover:bg-surface-muted dark:text-slate-400 dark:hover:bg-slate-800"
                        title="Toggle dark mode"
                    >
                        <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                        <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <x-button variant="ghost" type="submit" size="sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                            </svg>
                            Logout
                        </x-button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @if (session('success'))
                    <div class="mb-6">
                        <x-alert type="success">{{ session('success') }}</x-alert>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6">
                        <x-alert type="error">{{ session('error') }}</x-alert>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6">
                        <x-alert type="info">{{ session('status') }}</x-alert>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6">
                        <x-alert type="error">
                            <ul class="list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-confirm-dialog />

    <div
        x-show="loading"
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center bg-secondary-900/40 backdrop-blur-sm"
    >
        <div class="flex flex-col items-center gap-3 rounded-2xl bg-surface px-8 py-6 shadow-xl dark:bg-slate-900">
            <svg class="h-10 w-10 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm font-medium text-text-muted dark:text-slate-400">Processing...</p>
        </div>
    </div>

    @stack('scripts')
    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
