<x-layouts.app title="Router Management" subtitle="Connect and manage your MikroTik hotspot router">
    @if (! $router->isConfigured())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
            <p class="font-semibold">Hatua ya 1: Weka taarifa za MikroTik</p>
            <p class="mt-1">Jaza IP, username na password ya router yako hapa chini, kisha bonyeza <strong>Hifadhi Router Settings</strong> kabla ya Connect.</p>
        </div>
    @endif

    <div class="mb-6 card p-6">
        <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Router Configuration</h3>
        @include('router._settings-form')
    </div>

    <div
        x-data="{
            onlineUsers: [],
            offlineUsers: [],
            loadingOnline: false,
            loadingOffline: false,
            connected: {{ $router->is_connected ? 'true' : 'false' }},
            async fetchOnline() {
                this.loadingOnline = true;
                try {
                    const res = await fetch('{{ route('router.users.online') }}', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                    const data = await res.json();
                    this.onlineUsers = data.users || [];
                } catch (e) { this.onlineUsers = []; }
                this.loadingOnline = false;
            },
            async fetchOffline() {
                this.loadingOffline = true;
                try {
                    const res = await fetch('{{ route('router.users.offline') }}', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                    const data = await res.json();
                    this.offlineUsers = data.users || [];
                } catch (e) { this.offlineUsers = []; }
                this.loadingOffline = false;
            },
            init() {
                @if($router->router_ip && $router->router_username)
                    this.fetchOnline();
                    this.fetchOffline();
                @endif
            }
        }"
    >
        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="card p-6 lg:col-span-1">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">Connection Status</h3>
                    <span class="badge" :class="connected ? 'badge-success' : 'badge-danger'" x-text="connected ? 'Connected' : 'Disconnected'"></span>
                </div>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-text-muted">Router IP</dt><dd class="font-medium dark:text-white">{{ $router->router_ip ?? 'Not configured' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">Username</dt><dd class="font-medium dark:text-white">{{ $router->router_username ?? 'Not configured' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">Port</dt><dd class="font-medium dark:text-white">{{ $router->router_port ?? 8728 }}</dd></div>
                    <div class="flex justify-between"><dt class="text-text-muted">Last Synced</dt><dd class="font-medium dark:text-white">{{ $router->last_synced_at?->format('M d, Y H:i') ?? 'Never' }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('router.connect') }}" @submit="$dispatch('form-submitting')">
                        @csrf
                        <x-button type="submit" variant="primary" size="sm">Connect</x-button>
                    </form>
                    <form method="POST" action="{{ route('router.disconnect') }}" @submit="$dispatch('form-submitting')">
                        @csrf
                        <x-button type="submit" variant="outline" size="sm">Disconnect</x-button>
                    </form>
                    <form method="POST" action="{{ route('router.test') }}" @submit="$dispatch('form-submitting')">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm">Test Connection</x-button>
                    </form>
                    <form method="POST" action="{{ route('router.sync') }}" @submit="$dispatch('form-submitting')">
                        @csrf
                        <x-button type="submit" variant="success" size="sm">Sync Users</x-button>
                    </form>
                </div>
            </div>

            <div class="card p-6 lg:col-span-2">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Quick Actions</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-border p-4 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-primary-100 p-2 dark:bg-primary-900/40">
                                <svg class="h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 0 1 7.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M2.277 8.625c5.385-5.385 14.112-5.385 19.497 0M12 18.75h.008v.008H12v-.008Z" /></svg>
                            </div>
                            <div>
                                <p class="font-medium dark:text-white">Sync Active Users</p>
                                <p class="text-xs text-text-muted">Update voucher activation status</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border p-4 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/40">
                                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </div>
                            <div>
                                <p class="font-medium dark:text-white">Sync Expired Users</p>
                                <p class="text-xs text-text-muted">Remove expired hotspot sessions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-data-table title="Online Users" :description="'Active hotspot sessions'">
                <x-slot:actions>
                    <x-button type="button" variant="outline" size="sm" @click="fetchOnline()" ::disabled="loadingOnline">
                        <span x-show="!loadingOnline">Refresh</span>
                        <span x-show="loadingOnline">Loading...</span>
                    </x-button>
                </x-slot:actions>
                <x-slot:head>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">User</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">IP Address</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Uptime</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-text-muted">Action</th>
                </x-slot:head>
                <template x-for="(user, index) in onlineUsers" :key="index">
                    <tr class="hover:bg-surface-muted/50 dark:hover:bg-slate-800/50">
                        <td class="px-6 py-4 text-sm font-medium dark:text-white" x-text="user.user || user.name || '—'"></td>
                        <td class="px-6 py-4 text-sm dark:text-slate-300" x-text="user.address || '—'"></td>
                        <td class="px-6 py-4 text-sm dark:text-slate-300" x-text="user.uptime || '—'"></td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="{{ route('router.users.disconnect') }}" class="inline" @submit="$dispatch('form-submitting')">
                                @csrf
                                <input type="hidden" name="username" :value="user.user || user.name">
                                <x-button type="submit" variant="danger" size="sm">Disconnect</x-button>
                            </form>
                        </td>
                    </tr>
                </template>
                <tr x-show="onlineUsers.length === 0 && !loadingOnline">
                    <td colspan="4" class="px-6 py-8 text-center text-sm text-text-muted">No online users</td>
                </tr>
            </x-data-table>

            <x-data-table title="Offline Users" description="Registered but inactive users">
                <x-slot:actions>
                    <x-button type="button" variant="outline" size="sm" @click="fetchOffline()" ::disabled="loadingOffline">Refresh</x-button>
                </x-slot:actions>
                <x-slot:head>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Profile</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-text-muted">Comment</th>
                </x-slot:head>
                <template x-for="(user, index) in offlineUsers" :key="index">
                    <tr class="hover:bg-surface-muted/50 dark:hover:bg-slate-800/50">
                        <td class="px-6 py-4 text-sm font-medium dark:text-white" x-text="user.name || '—'"></td>
                        <td class="px-6 py-4 text-sm dark:text-slate-300" x-text="user.profile || '—'"></td>
                        <td class="px-6 py-4 text-sm dark:text-slate-300" x-text="user.comment || '—'"></td>
                    </tr>
                </template>
                <tr x-show="offlineUsers.length === 0 && !loadingOffline">
                    <td colspan="3" class="px-6 py-8 text-center text-sm text-text-muted">No offline users</td>
                </tr>
            </x-data-table>
        </div>
    </div>
</x-layouts.app>
