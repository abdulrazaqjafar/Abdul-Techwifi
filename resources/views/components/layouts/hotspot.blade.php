@props(['settings' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="hotspotApp()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ __('hotspot.tagline') }}">
    <meta name="theme-color" content="#16a34a">
    <title>{{ ($settings->business_name ?? 'Tech-Sphere Wi-Fi') }} — {{ __('hotspot.welcome') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:500,600,700,800|manrope:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('hotspot-config')
</head>
<body class="hotspot-body min-h-screen antialiased">
    {{ $slot }}

    <div
        x-show="toast.show"
        x-transition
        x-cloak
        class="fixed bottom-6 left-1/2 z-[100] w-[90%] max-w-md -translate-x-1/2"
        role="alert"
        aria-live="polite"
    >
        <div
            class="flex items-center gap-3 rounded-2xl px-4 py-3 shadow-2xl backdrop-blur-md"
            :class="toast.type === 'error' ? 'bg-red-600 text-white' : 'bg-primary-600 text-white'"
        >
            <span x-text="toast.message" class="text-sm font-medium"></span>
            <button type="button" @click="toast.show = false" class="ml-auto opacity-80 hover:opacity-100" aria-label="Close">&times;</button>
        </div>
    </div>
</body>
</html>
