<form method="POST" action="{{ route('settings.router.update') }}" class="space-y-4" @submit="$dispatch('form-submitting')">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="router_ip" class="label">Router IP <span class="text-red-500">*</span></label>
            <input type="text" id="router_ip" name="router_ip" value="{{ old('router_ip', $router->router_ip) }}" required class="input-field" placeholder="192.168.10.1">
        </div>
        <div>
            <label for="router_port" class="label">Port <span class="text-red-500">*</span></label>
            <input type="number" id="router_port" name="router_port" value="{{ old('router_port', $router->router_port ?? 8728) }}" required min="1" max="65535" class="input-field">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="router_username" class="label">Username <span class="text-red-500">*</span></label>
            <input type="text" id="router_username" name="router_username" value="{{ old('router_username', $router->router_username) }}" required class="input-field" placeholder="admin">
            <p class="mt-1 text-xs text-text-muted dark:text-slate-400">Username ya MikroTik (Winbox), si email ya admin wa mfumo.</p>
        </div>
        <div>
            <label for="router_password" class="label">
                Password
                @if (! $router->isConfigured())
                    <span class="text-red-500">*</span>
                @endif
            </label>
            <input
                type="password"
                id="router_password"
                name="router_password"
                class="input-field"
                @if (! $router->isConfigured()) required @endif
                placeholder="{{ $router->isConfigured() ? 'Acha tupu kama hautaki kubadilisha' : 'Weka nenosiri la MikroTik' }}"
            >
        </div>
    </div>

    <div>
        <label for="hotspot_interface" class="label">Hotspot Interface</label>
        <input type="text" id="hotspot_interface" name="hotspot_interface" value="{{ old('hotspot_interface', $router->hotspot_interface ?? 'hotspot') }}" class="input-field" placeholder="hotspot">
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4 dark:border-slate-700">
        <p class="text-xs text-text-muted dark:text-slate-400">
            Hakikisha PC yako iko kwenye network ile ile na MikroTik (mfano: 192.168.10.x).
            Kwenye MikroTik: <strong>IP → Services → api</strong> iwe <strong>enabled</strong>, port <strong>8728</strong>.
        </p>
        <x-button type="submit" variant="primary">Hifadhi Router Settings</x-button>
    </div>
</form>
