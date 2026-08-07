<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HotspotAuthService
{
    public function __construct(
        protected MikrotikRouterService $routerService,
        protected VoucherService $voucherService,
        protected DeviceDetectionService $deviceDetection
    ) {}

    public function validateVoucher(string $code): Voucher
    {
        $voucher = Voucher::query()
            ->with('package')
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $voucher) {
            throw new RuntimeException(__('hotspot.errors.invalid_voucher'));
        }

        if ($voucher->isCancelled()) {
            throw new RuntimeException(__('hotspot.errors.cancelled_voucher'));
        }

        if ($voucher->isExpired()) {
            throw new RuntimeException(__('hotspot.errors.expired_voucher'));
        }

        if ($voucher->isUsed()) {
            throw new RuntimeException(__('hotspot.errors.used_voucher'));
        }

        if (! $voucher->package?->is_active) {
            throw new RuntimeException(__('hotspot.errors.inactive_package'));
        }

        return $voucher;
    }

    /**
     * Activate or reconnect a voucher for the requesting device and grant router access.
     *
     * @param  array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}  $device
     * @return array{voucher: Voucher, redirect_url: ?string, connected: bool}
     */
    public function connectVoucherWithDevice(string $code, ?string $linkLogin, array $device): array
    {
        $voucher = Voucher::query()
            ->with('package')
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $voucher) {
            throw new RuntimeException(__('hotspot.errors.invalid_voucher'));
        }

        if ($voucher->isCancelled()) {
            throw new RuntimeException(__('hotspot.errors.cancelled_voucher'));
        }

        if ($voucher->isExpired()) {
            throw new RuntimeException(__('hotspot.errors.expired_voucher'));
        }

        if (! $voucher->package?->is_active) {
            throw new RuntimeException(__('hotspot.errors.inactive_package'));
        }

        $device = $this->resolveDeviceIdentity($device);

        if ($voucher->isUnused()) {
            return $this->activateAndConnect($voucher, $linkLogin, $device);
        }

        return $this->reconnectDevice($voucher, $linkLogin, $device);
    }

    /**
     * @param  array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}  $device
     * @return array{voucher: Voucher, redirect_url: ?string, connected: bool}
     */
    public function activateAndConnect(Voucher $voucher, ?string $linkLogin = null, array $device = []): array
    {
        $profile = $this->deviceDetection->buildProfile($device, $voucher);

        // Grant router access before burning the voucher, so a router failure
        // never leaves the customer with a used code and no internet.
        $access = $this->syncRouterAccess($voucher, $profile, $device, $linkLogin);

        if ($voucher->isUnused()) {
            $voucher = $this->voucherService->markAsUsed($voucher);
        }

        $voucher = $this->deviceDetection->persistOnVoucher($voucher, $access['profile'], force: true);

        return [
            'voucher' => $voucher->fresh(['package']),
            'redirect_url' => $this->buildLoginRedirectUrl($voucher, $linkLogin),
            'connected' => $access['connected'],
        ];
    }

    /**
     * Reconnect a device that already has an active voucher on this MAC.
     *
     * @param  array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}  $device
     * @return array{voucher: Voucher, redirect_url: ?string, connected: bool}
     */
    public function reconnectDevice(Voucher $voucher, ?string $linkLogin = null, array $device = []): array
    {
        if (! $voucher->isUsed() || $voucher->isCancelled()) {
            throw new RuntimeException(__('hotspot.errors.invalid_voucher'));
        }

        if ($voucher->isExpired()) {
            throw new RuntimeException(__('hotspot.errors.expired_voucher'));
        }

        $normalizedMac = $this->deviceDetection->normalizeMac($device['mac'] ?? null);

        if ($voucher->activation_mac && $normalizedMac && $voucher->activation_mac !== $normalizedMac) {
            throw new RuntimeException(__('hotspot.errors.device_mismatch'));
        }

        $profile = $this->deviceDetection->buildProfile($device, $voucher);
        $profile['activation_mac'] = $voucher->activation_mac ?: $profile['activation_mac'];

        $access = $this->syncRouterAccess($voucher, $profile, $device, $linkLogin);
        $voucher = $this->deviceDetection->persistOnVoucher($voucher, $access['profile'], force: true);

        return [
            'voucher' => $voucher->fresh(['package']),
            'redirect_url' => $this->buildLoginRedirectUrl($voucher, $linkLogin),
            'connected' => $access['connected'],
        ];
    }

    /**
     * Fill in the MAC address from the router when the captive portal did not supply it.
     *
     * @param  array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}  $device
     * @return array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}
     */
    protected function resolveDeviceIdentity(array $device): array
    {
        $mac = $this->deviceDetection->normalizeMac($device['mac'] ?? null);
        $ip = $device['ip'] ?? null;

        if ($mac) {
            $device['mac'] = $mac;

            return $device;
        }

        if (blank($ip) || ! $this->routerService->isConfigured()) {
            return $device;
        }

        try {
            $this->routerService->connect();

            $host = $this->routerService->getHotspotHostByIp($ip)
                ?? $this->routerService->getDhcpLeaseByIp($ip);

            if ($host && ! blank($host['mac-address'] ?? null)) {
                $device['mac'] = $this->deviceDetection->normalizeMac($host['mac-address']);
            }
        } catch (RuntimeException $exception) {
            Log::info('Hotspot device lookup by IP failed', [
                'ip' => $ip,
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $this->routerService->disconnect();
        }

        return $device;
    }

    /**
     * @param  array<string, ?string>  $profile
     * @param  array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}  $device
     * @return array{profile: array<string, ?string>, connected: bool}
     */
    protected function syncRouterAccess(Voucher $voucher, array $profile, array $device, ?string $linkLogin): array
    {
        if (! $this->routerService->isConfigured()) {
            return ['profile' => $profile, 'connected' => false];
        }

        $connected = false;

        try {
            $this->routerService->connect();
            $this->provisionRouterUser($voucher);

            $ip = $profile['activation_ip'] ?: ($device['ip'] ?? null);

            // Without link-login the phone cannot post to the portal itself,
            // so authorise the session through the router API instead.
            if (blank($linkLogin) && filled($ip)) {
                $connected = $this->routerService->loginHotspotUser(
                    $voucher->mikrotik_username ?? $voucher->code,
                    $voucher->code,
                    $ip,
                    $profile['activation_mac']
                );
            }

            $routerDevice = $this->deviceDetection->fetchFromRouter(
                $this->routerService,
                $voucher->mikrotik_username ?? $voucher->code,
                $profile['activation_mac']
            );

            $profile = $this->deviceDetection->mergeProfiles($profile, $routerDevice);
        } catch (RuntimeException $exception) {
            Log::warning('Hotspot router provisioning failed', [
                'voucher' => $voucher->code,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(__('hotspot.errors.router_provision_failed'));
        } finally {
            $this->routerService->disconnect();
        }

        return ['profile' => $profile, 'connected' => $connected];
    }

    public function findActiveVoucherForDevice(?string $mac): ?Voucher
    {
        $normalized = $this->deviceDetection->normalizeMac($mac);

        if (! $normalized) {
            return null;
        }

        return Voucher::query()
            ->with('package')
            ->where('status', Voucher::STATUS_USED)
            ->where('activation_mac', $normalized)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('activated_at')
            ->first();
    }

    protected function provisionRouterUser(Voucher $voucher): void
    {
        $package = $voucher->package;
        $username = $voucher->mikrotik_username ?? $voucher->code;
        $profile = $package->profile_name ?? 'default';
        $limitUptime = $this->resolveLimitUptime($package);

        $this->routerService->ensureHotspotUser(
            $username,
            $voucher->code,
            $profile,
            $limitUptime
        );
    }

    protected function resolveLimitUptime(Package $package): ?string
    {
        if ($package->duration_days > 0) {
            return $package->duration_days.'d';
        }

        if ($package->duration_hours > 0) {
            return $package->duration_hours.'h';
        }

        return null;
    }

    protected function buildLoginRedirectUrl(Voucher $voucher, ?string $linkLogin): ?string
    {
        if (blank($linkLogin)) {
            return null;
        }

        $username = $voucher->mikrotik_username ?? $voucher->code;
        $separator = str_contains($linkLogin, '?') ? '&' : '?';

        return $linkLogin.$separator.http_build_query([
            'username' => $username,
            'password' => $voucher->code,
        ]);
    }
}
