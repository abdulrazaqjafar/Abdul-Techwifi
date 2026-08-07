<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="darkMode()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} - Tech-Sphere Wi-fi Billing System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-secondary-800 via-secondary-900 to-slate-950">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -left-20 top-20 h-72 w-72 rounded-full bg-primary-600/20 blur-3xl"></div>
        <div class="absolute -right-20 bottom-20 h-96 w-96 rounded-full bg-primary-500/10 blur-3xl"></div>
    </div>

    <div class="relative flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-600 shadow-xl shadow-primary-600/30">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M2.277 8.625c5.385-5.385 14.112-5.385 19.497 0M12 18.75h.008v.008H12v-.008Z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Tech-Sphere Wi-fi Billing System</h1>
            <p class="mt-2 text-sm text-slate-400">Manage your hotspot business with ease</p>
        </div>

        <div class="w-full max-w-md animate-slide-up rounded-2xl border border-white/10 bg-white/95 p-8 shadow-2xl backdrop-blur dark:bg-slate-900/95">
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
        </div>

        <p class="mt-8 text-center text-xs text-slate-500">&copy; {{ date('Y') }} Tech-Sphere. All rights reserved.</p>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
