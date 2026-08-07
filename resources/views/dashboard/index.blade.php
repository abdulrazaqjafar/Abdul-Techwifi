@php
    $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'TSh';
@endphp

<x-layouts.app title="Dashboard" subtitle="Live analytics na muhtasari wa shughuli zako">
    <div
        x-data="liveDashboard({
            stats: @js($live['stats']),
            recentSales: @js($live['recent_sales']),
            networkQuality: @js($live['network_quality'] ?? ['users' => [], 'summary' => []]),
            paymentMode: @js($paymentMode),
            updatedAt: @js($live['updated_at']),
            liveUrl: @js(route('dashboard.live')),
            currency: @js($currency),
        })"
    >
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                    </span>
                    Live Analytics
                </span>
                <span class="text-xs text-text-muted dark:text-slate-400">Imesasishwa: <span x-text="updatedAt">{{ $live['updated_at'] }}</span></span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge badge-success" x-text="paymentMode">{{ $paymentMode }}</span>
                <x-button type="button" variant="outline" size="sm" @click="refresh()">Sasaisha Sasa</x-button>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card p-5">
                <p class="text-sm font-medium text-text-muted dark:text-slate-400">Mapato Leo (Live)</p>
                <p class="mt-2 text-3xl font-bold text-secondary-800 dark:text-white" x-text="formatMoney(stats.today_revenue)">{{ number_format($live['stats']['today_revenue'], 0) }} {{ $currency }}</p>
                <p class="mt-2 text-xs text-primary-600" x-text="`${stats.today_sales ?? 0} mauzo leo`">{{ $live['stats']['today_sales'] }} mauzo leo</p>
            </div>
            <div class="card p-5">
                <p class="text-sm font-medium text-text-muted dark:text-slate-400">Saa Iliyopita</p>
                <p class="mt-2 text-3xl font-bold text-secondary-800 dark:text-white" x-text="formatMoney(stats.last_hour_revenue)">{{ number_format($live['stats']['last_hour_revenue'], 0) }} {{ $currency }}</p>
                <p class="mt-2 text-xs text-text-muted" x-text="`${stats.last_hour_sales ?? 0} mauzo`">{{ $live['stats']['last_hour_sales'] }} mauzo</p>
            </div>
            <div class="card p-5">
                <p class="text-sm font-medium text-text-muted dark:text-slate-400">Wateja Online</p>
                <p class="mt-2 text-3xl font-bold text-secondary-800 dark:text-white" x-text="stats.online_users ?? 0">{{ $live['stats']['online_users'] }}</p>
                <p class="mt-2 text-xs text-text-muted">
                    <span class="text-green-600" x-text="`${stats.quality_good ?? 0} nzuri`">{{ $live['stats']['quality_good'] ?? 0 }} nzuri</span> ·
                    <span class="text-amber-600" x-text="`${stats.quality_fair ?? 0} wastani`">{{ $live['stats']['quality_fair'] ?? 0 }} wastani</span> ·
                    <span class="text-red-600" x-text="`${stats.quality_poor ?? 0} dhaifu`">{{ $live['stats']['quality_poor'] ?? 0 }} dhaifu</span>
                </p>
            </div>
            <div class="card p-5">
                <p class="text-sm font-medium text-text-muted dark:text-slate-400">Malipo Yanayosubiri</p>
                <p class="mt-2 text-3xl font-bold text-secondary-800 dark:text-white" x-text="stats.pending_payments ?? 0">{{ $live['stats']['pending_payments'] }}</p>
                <p class="mt-2 text-xs text-text-muted">Hotspot mobile money</p>
            </div>
        </div>

        <div class="mb-8 card overflow-hidden">
            <div class="border-b border-border px-6 py-4 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">Ubora wa Mtandao — Watumiaji Walioko Online (Live)</h3>
                <p class="mt-1 text-xs text-text-muted">Inasasishwa kila sekunde 15 kutoka MikroTik router</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-surface-muted text-left text-xs uppercase text-text-muted dark:border-slate-700 dark:bg-slate-800">
                            <th class="px-6 py-3">Voucher</th>
                            <th class="px-6 py-3">Mteja</th>
                            <th class="px-6 py-3">Package</th>
                            <th class="px-6 py-3">MAC</th>
                            <th class="px-6 py-3">IP</th>
                            <th class="px-6 py-3">Kifaa</th>
                            <th class="px-6 py-3">Chanzo</th>
                            <th class="px-6 py-3">Ubora</th>
                            <th class="px-6 py-3">Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!networkQuality.users || networkQuality.users.length === 0">
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-text-muted">Hakuna mtumiaji online kwa sasa.</td>
                            </tr>
                        </template>
                        <template x-for="user in networkQuality.users" :key="user.username">
                            <tr class="border-b border-border/60 dark:border-slate-800">
                                <td class="whitespace-nowrap px-6 py-3 font-mono text-primary-600" x-text="user.voucher_code ?? user.username"></td>
                                <td class="px-6 py-3 dark:text-white" x-text="user.customer ?? '—'"></td>
                                <td class="px-6 py-3 dark:text-white" x-text="user.package ?? '—'"></td>
                                <td class="whitespace-nowrap px-6 py-3 font-mono text-xs dark:text-slate-300" x-text="user.mac ?? '—'"></td>
                                <td class="whitespace-nowrap px-6 py-3 font-mono text-xs dark:text-slate-300" x-text="user.ip ?? '—'"></td>
                                <td class="px-6 py-3 dark:text-white" x-text="user.device ?? '—'"></td>
                                <td class="px-6 py-3 capitalize dark:text-white" x-text="user.source ?? '—'"></td>
                                <td class="px-6 py-3">
                                    <span
                                        class="badge"
                                        :class="{
                                            'badge-success': user.quality_score === 'good',
                                            'badge-warning': user.quality_score === 'fair',
                                            'badge-danger': user.quality_score === 'poor',
                                            'badge-neutral': !['good','fair','poor'].includes(user.quality_score),
                                        }"
                                        x-text="user.quality_label"
                                    ></span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-3 text-text-muted" x-text="user.uptime ?? '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="card p-6 xl:col-span-2">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Mauzo ya Leo (Kwa Saa)</h3>
                <div class="h-64" x-data="chartWidget({
                    type: 'bar',
                    data: {
                        labels: @js($live['hourly_today']['labels']),
                        datasets: [{
                            label: 'Revenue',
                            data: @js($live['hourly_today']['revenues']),
                            backgroundColor: '#16a34a',
                            borderRadius: 8,
                        }]
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } },
                        x: { grid: { display: false } }
                    }
                })" x-ref="hourlyChart">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Malipo Leo</h3>
                <div class="h-64" x-data="chartWidget({
                    type: 'doughnut',
                    data: {
                        labels: @js($live['payment_methods']['labels']),
                        datasets: [{
                            data: @js($live['payment_methods']['amounts']),
                            backgroundColor: ['#16a34a', '#1e3a5f', '#22c55e', '#334e68', '#f59e0b'],
                        }]
                    },
                    options: { scales: {} }
                })">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        <div class="mb-8 card overflow-hidden">
            <div class="border-b border-border px-6 py-4 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-secondary-800 dark:text-white">Shughuli za Hivi Karibuni (Live Feed)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-surface-muted text-left text-xs uppercase text-text-muted dark:border-slate-700 dark:bg-slate-800">
                            <th class="px-6 py-3">Muda</th>
                            <th class="px-6 py-3">Package</th>
                            <th class="px-6 py-3">Mteja</th>
                            <th class="px-6 py-3">Malipo</th>
                            <th class="px-6 py-3">Kiasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="recentSales.length === 0">
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-text-muted">Hakuna mauzo bado leo.</td>
                            </tr>
                        </template>
                        <template x-for="sale in recentSales" :key="sale.id">
                            <tr class="border-b border-border/60 dark:border-slate-800">
                                <td class="whitespace-nowrap px-6 py-3 text-text-muted" x-text="sale.sold_at"></td>
                                <td class="px-6 py-3 dark:text-white" x-text="sale.package"></td>
                                <td class="px-6 py-3 dark:text-white" x-text="sale.customer"></td>
                                <td class="px-6 py-3 dark:text-white" x-text="sale.method"></td>
                                <td class="px-6 py-3 font-semibold text-primary-600" x-text="sale.amount_formatted"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card title="Total Revenue" :value="number_format($stats['total_revenue'], 0) . ' ' . $currency" icon="currency" color="primary" />
            <x-stat-card title="Total Sales" :value="number_format($stats['total_sales'])" icon="currency" color="blue" />
            <x-stat-card title="Total Vouchers" :value="number_format($stats['total_vouchers'])" icon="ticket" color="secondary" />
            <x-stat-card title="Total Customers" :value="number_format($stats['total_customers'])" icon="users" color="amber" />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="card p-6">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Daily Sales (7 Days)</h3>
                <div class="h-64" x-data="chartWidget({
                    type: 'line',
                    data: {
                        labels: @js($dailyChart['labels']),
                        datasets: [
                            {
                                label: 'Revenue',
                                data: @js($dailyChart['revenues']),
                                borderColor: '#16a34a',
                                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                                fill: true,
                                tension: 0.4,
                            },
                            {
                                label: 'Sales Count',
                                data: @js($dailyChart['counts']),
                                borderColor: '#1e3a5f',
                                backgroundColor: 'rgba(30, 58, 95, 0.1)',
                                fill: false,
                                tension: 0.4,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
                        x: { grid: { display: false } }
                    }
                })">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="mb-4 text-lg font-semibold text-secondary-800 dark:text-white">Package Popularity</h3>
                <div class="h-64" x-data="chartWidget({
                    type: 'doughnut',
                    data: {
                        labels: @js($packageChart['labels']),
                        datasets: [{
                            data: @js($packageChart['counts']),
                            backgroundColor: ['#16a34a', '#1e3a5f', '#22c55e', '#334e68', '#4ade80', '#486581', '#86efac', '#627d98', '#bbf7d0', '#829ab1'],
                        }]
                    },
                    options: { scales: {} }
                })">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
