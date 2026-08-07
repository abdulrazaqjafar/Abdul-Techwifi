import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './hotspot';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('darkMode', () => ({
    dark: localStorage.getItem('darkMode') === 'true',

    init() {
        this.apply();
    },

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('darkMode', this.dark);
        this.apply();
    },

    apply() {
        document.documentElement.classList.toggle('dark', this.dark);
    },
}));

Alpine.data('loadingOverlay', () => ({
    loading: false,

    show() {
        this.loading = true;
    },

    hide() {
        this.loading = false;
    },
}));

Alpine.data('confirmDialog', () => ({
    open: false,
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    form: null,

    show(options = {}) {
        this.title = options.title ?? this.title;
        this.message = options.message ?? this.message;
        this.confirmText = options.confirmText ?? this.confirmText;
        this.cancelText = options.cancelText ?? this.cancelText;
        this.form = options.form ?? null;
        this.open = true;
    },

    confirm() {
        if (this.form) {
            this.form.submit();
        }
        this.open = false;
    },

    cancel() {
        this.open = false;
        this.form = null;
    },
}));

Alpine.data('chartWidget', (config) => ({
    chart: null,

    init() {
        const canvas = this.$refs.canvas;
        if (!canvas || !config) return;

        this.chart = new Chart(canvas, {
            type: config.type ?? 'line',
            data: config.data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569',
                        },
                    },
                },
                scales: config.scales ?? {},
                ...config.options,
            },
        });
    },

    updateChart(data) {
        if (!this.chart || !data) return;
        this.chart.data.labels = data.labels ?? this.chart.data.labels;
        if (this.chart.data.datasets[0]) {
            this.chart.data.datasets[0].data = data.revenues ?? data.counts ?? data.amounts ?? this.chart.data.datasets[0].data;
        }
        if (this.chart.data.datasets[1] && data.counts) {
            this.chart.data.datasets[1].data = data.counts;
        }
        this.chart.update('none');
    },
}));

Alpine.data('liveDashboard', (config) => ({
    stats: config.stats ?? {},
    recentSales: config.recentSales ?? [],
    networkQuality: config.networkQuality ?? { users: [], summary: {} },
    paymentMode: config.paymentMode ?? '',
    updatedAt: config.updatedAt ?? '',
    polling: null,
    liveUrl: config.liveUrl,

    init() {
        this.startPolling();
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.refresh();
                this.startPolling();
            }
        });
    },

    startPolling() {
        this.stopPolling();
        this.polling = setInterval(() => this.refresh(), 15000);
    },

    stopPolling() {
        if (this.polling) {
            clearInterval(this.polling);
            this.polling = null;
        }
    },

    async refresh() {
        try {
            const response = await fetch(this.liveUrl, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;
            const data = await response.json();
            this.stats = data.stats ?? this.stats;
            this.recentSales = data.recent_sales ?? this.recentSales;
            this.networkQuality = data.network_quality ?? this.networkQuality;
            this.paymentMode = data.payment_mode ?? this.paymentMode;
            this.updatedAt = data.updated_at ?? this.updatedAt;
            this.$dispatch('live-dashboard-updated', data);
        } catch {
            // ignore transient network errors
        }
    },

    formatMoney(value) {
        const currency = config.currency ?? 'TSh';
        return `${Number(value || 0).toLocaleString()} ${currency}`;
    },
}));

Alpine.start();
