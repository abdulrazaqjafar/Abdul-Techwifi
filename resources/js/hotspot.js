import Alpine from 'alpinejs';

Alpine.data('hotspotApp', () => ({
    dark: localStorage.getItem('hotspotDark') === 'true',
    rememberVoucher: localStorage.getItem('rememberVoucher') !== 'false',
    voucherCode: localStorage.getItem('lastVoucher') || '',
    loading: false,
    loginLoading: false,

    modalOpen: false,
    modalStep: 'phone',
    selectedPackage: null,
    phone: '',
    provider: 'simulated',
    paymentReference: null,
    paymentPolling: null,
    paymentMessage: '',

    successOpen: false,
    successData: null,
    confettiActive: false,
    copied: false,

    toast: { show: false, message: '', type: 'success' },
    autoConnecting: false,

    mikrotik: { mac: '', ip: '', link_login: '', link_orig: '' },
    routes: {},
    i18n: {},
    businessName: '',

    init() {
        const config = window.__HOTSPOT__ || {};
        this.routes = config.routes || {};
        this.i18n = config.i18n || {};
        this.provider = config.defaultProvider || 'simulated';
        this.mikrotik = config.mikrotik || this.mikrotik;
        this.businessName = config.businessName || 'Tech-Sphere Wi-Fi';
        this.applyDark();

        this.tryAutoConnect().then((connected) => {
            if (!connected && this.shouldFocusPackages()) {
                requestAnimationFrame(() => this.scrollToPackages());
            }
        });
    },

    async tryAutoConnect() {
        if (!this.mikrotik.mac || !this.mikrotik.link_login || !this.routes.autoConnect) {
            return false;
        }

        this.autoConnecting = true;

        try {
            const result = await this.apiPost(this.routes.autoConnect, {});

            if (result.success && result.redirect_url) {
                this.showToast(result.message || this.i18n.auto_reconnect || 'Connecting...');
                setTimeout(() => { window.location.href = result.redirect_url; }, 800);
                return true;
            }
        } catch {
            // No active voucher — show purchase page.
        } finally {
            this.autoConnecting = false;
        }

        return false;
    },

    shouldFocusPackages() {
        const params = new URLSearchParams(window.location.search);

        if (params.get('view') === 'packages' || window.location.hash === '#packages') {
            return true;
        }

        // Captive portal "Sign in to network" kutoka MikroTik huja na link-login
        return Boolean(
            params.get('link-login')
            || params.get('link_login')
            || this.mikrotik.link_login
        );
    },

    scrollToPackages() {
        const section = document.getElementById('packages-section');

        if (!section) {
            return;
        }

        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    openPurchaseFromCard(el) {
        this.openPurchase({
            id: Number(el.dataset.packageId),
            name: el.dataset.packageName,
            price: Number(el.dataset.packagePrice),
            duration: el.dataset.packageDuration,
            icon: el.dataset.packageIcon,
        });
    },

    applyDark() {
        document.documentElement.classList.toggle('dark', this.dark);
    },

    toggleDark() {
        this.dark = !this.dark;
        localStorage.setItem('hotspotDark', this.dark);
        this.applyDark();
    },

    toggleRemember() {
        this.rememberVoucher = !this.rememberVoucher;
        localStorage.setItem('rememberVoucher', this.rememberVoucher);
        if (!this.rememberVoucher) {
            localStorage.removeItem('lastVoucher');
        }
    },

    showToast(message, type = 'success') {
        this.toast = { show: true, message, type };
        const duration = String(message || '').length > 80 ? 15000 : 5000;
        setTimeout(() => { this.toast.show = false; }, duration);
    },

    formatExpiry(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString(undefined, {
                dateStyle: 'medium',
                timeStyle: 'short',
            });
        } catch {
            return iso;
        }
    },

    async copyVoucher() {
        if (!this.successData?.code) return;
        try {
            await navigator.clipboard.writeText(this.successData.code);
            this.copied = true;
            this.showToast(this.i18n.copied || 'Copied!');
            setTimeout(() => { this.copied = false; }, 2500);
        } catch {
            this.showToast(this.i18n.network_error || 'Could not copy', 'error');
        }
    },

    downloadReceipt() {
        if (!this.successData) return;
        const lines = [
            `${this.businessName}`,
            'Wi-Fi Voucher Receipt',
            '--------------------------------',
            `Voucher: ${this.successData.code}`,
            `Package: ${this.successData.package_name}`,
            `Duration: ${this.successData.duration}`,
            `Price: TSh ${this.successData.price || ''}`,
            `Expires: ${this.formatExpiry(this.successData.expires_at)}`,
            `Date: ${new Date().toLocaleString()}`,
            '--------------------------------',
            'Powered by Tech-Sphere Wi-Fi Billing System',
        ];
        const blob = new Blob([lines.join('\n')], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `receipt-${this.successData.code}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    },

    deviceInfo() {
        return {
            user_agent: navigator.userAgent || '',
            screen_width: window.screen?.width || null,
        };
    },

    async apiPost(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ ...data, ...this.mikrotik, ...this.deviceInfo() }),
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(json.message || 'Request failed');
        return json;
    },

    async loginVoucher() {
        if (!this.voucherCode.trim()) return;

        this.loginLoading = true;
        try {
            const result = await this.apiPost(this.routes.login, {
                voucher_code: this.voucherCode.trim().toUpperCase(),
                link_login: this.mikrotik.link_login,
            });
            if (this.rememberVoucher) {
                localStorage.setItem('lastVoucher', this.voucherCode.trim().toUpperCase());
            }
            this.showToast(result.message);

            if (result.redirect_url) {
                window.location.replace(result.redirect_url);
            } else if (result.connected) {
                setTimeout(() => window.location.reload(), 1500);
            }
        } catch (e) {
            this.showToast(e.message, 'error');
        } finally {
            this.loginLoading = false;
        }
    },

    openPurchase(pkg) {
        this.selectedPackage = pkg;
        this.phone = '';
        this.modalStep = 'phone';
        this.modalOpen = true;
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        this.modalOpen = false;
        this.modalStep = 'phone';
        this.stopPolling();
        document.body.style.overflow = '';
    },

    async submitPhone() {
        if (!this.phone.trim()) return;
        this.loading = true;
        try {
            const result = await this.apiPost(this.routes.purchase, {
                package_id: this.selectedPackage.id,
                phone: this.phone,
                provider: this.provider,
            });
            this.paymentReference = result.reference;
            this.modalStep = 'payment';
            this.paymentMessage = this.i18n.waiting_payment || 'Waiting for payment...';
            this.startPolling();
        } catch (e) {
            this.showToast(e.message, 'error');
        } finally {
            this.loading = false;
        }
    },

    startPolling() {
        this.stopPolling();
        // Entering a Mobile Money PIN takes tens of seconds, so asking more
        // often than this only adds load without telling the customer anything
        // new.
        this.paymentPolling = setInterval(() => this.checkPayment(), 4000);
        this.checkPayment();
    },

    stopPolling() {
        if (this.paymentPolling) {
            clearInterval(this.paymentPolling);
            this.paymentPolling = null;
        }
    },

    async checkPayment() {
        if (!this.paymentReference) return;
        try {
            const res = await fetch(`${this.routes.paymentStatus}/${this.paymentReference}/status`, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();

            if (data.status === 'processing' || data.status === 'pending') {
                this.paymentMessage = data.message || this.i18n.waiting_payment;
                return;
            }

            this.stopPolling();

            if (data.status === 'completed' && data.voucher) {
                this.closeModal();
                this.successData = data.voucher;
                this.voucherCode = data.voucher.code;
                if (this.rememberVoucher) {
                    localStorage.setItem('lastVoucher', data.voucher.code);
                }
                this.successOpen = true;
                document.body.style.overflow = 'hidden';
                this.triggerConfetti();
                setTimeout(() => this.connectAfterPurchase(), 1500);
            } else {
                this.showToast(data.message || this.i18n.payment_failed, 'error');
                this.closeModal();
            }
        } catch {
            this.showToast(this.i18n.network_error || 'Network error', 'error');
        }
    },

    async connectAfterPurchase() {
        if (!this.successData?.code) return;
        this.loading = true;
        try {
            const result = await this.apiPost(this.routes.connect, {
                voucher_code: this.successData.code,
                link_login: this.mikrotik.link_login,
            });
            this.showToast(result.message);
            if (result.redirect_url) {
                window.location.replace(result.redirect_url);
            }
        } catch (e) {
            this.showToast(e.message, 'error');
        } finally {
            this.loading = false;
        }
    },

    triggerConfetti() {
        this.confettiActive = true;
        const canvas = document.getElementById('confetti-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const pieces = Array.from({ length: 140 }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height - canvas.height,
            r: Math.random() * 6 + 4,
            d: Math.random() * 80 + 40,
            color: ['#16a34a', '#1e3a8a', '#fbbf24', '#ffffff'][Math.floor(Math.random() * 4)],
        }));
        let frame = 0;
        const draw = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach((p) => {
                p.y += Math.cos(p.d) + p.r / 2;
                p.x += Math.sin(frame) * 2;
                ctx.beginPath();
                ctx.fillStyle = p.color;
                ctx.fillRect(p.x, p.y, p.r, p.r * 0.6);
            });
            frame += 1;
            if (frame < 200) requestAnimationFrame(draw);
            else this.confettiActive = false;
        };
        draw();
    },
}));
