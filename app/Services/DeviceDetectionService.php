<?php

namespace App\Services;

use App\Models\HotspotTransaction;
use App\Models\Voucher;

class DeviceDetectionService
{
    /**
     * @param  array{mac?: ?string, ip?: ?string, user_agent?: ?string, screen_width?: ?int}  $client
     * @return array<string, ?string>
     */
    public function buildProfile(array $client, ?Voucher $voucher = null): array
    {
        $mac = $this->normalizeMac($client['mac'] ?? null);
        $ip = $client['ip'] ?? null;

        if ((! $mac || ! $ip) && $voucher?->payment_reference) {
            $transaction = HotspotTransaction::query()
                ->where('reference', $voucher->payment_reference)
                ->first();

            $mac = $mac ?: $this->normalizeMac($transaction?->mac_address);
            $ip = $ip ?: $transaction?->ip_address;
        }

        $parsed = $this->parseUserAgent(
            $client['user_agent'] ?? null,
            isset($client['screen_width']) ? (int) $client['screen_width'] : null
        );

        return [
            'activation_mac' => $mac,
            'activation_ip' => $ip,
            'device_type' => $parsed['type'],
            'device_os' => $parsed['os'],
            'device_browser' => $parsed['browser'],
            'device_hostname' => null,
        ];
    }

    /**
     * @param  array<string, ?string>  $base
     * @param  array<string, ?string>  $router
     * @return array<string, ?string>
     */
    public function mergeProfiles(array $base, array $router): array
    {
        return [
            'activation_mac' => $base['activation_mac'] ?: $this->normalizeMac($router['mac'] ?? null),
            'activation_ip' => $base['activation_ip'] ?: ($router['ip'] ?? null),
            'device_type' => $base['device_type'] ?: ($router['device_type'] ?? null),
            'device_os' => $base['device_os'] ?: ($router['device_os'] ?? null),
            'device_browser' => $base['device_browser'] ?: ($router['device_browser'] ?? null),
            'device_hostname' => $base['device_hostname'] ?: ($router['hostname'] ?? null),
        ];
    }

    /**
     * @return array{mac: ?string, ip: ?string, hostname: ?string, device_type: ?string, device_os: ?string, device_browser: ?string}
     */
    public function fetchFromRouter(MikrotikRouterService $routerService, string $username, ?string $mac = null): array
    {
        $session = $routerService->getActiveSessionForUser($username);

        if (! $session && $mac) {
            $session = $routerService->getActiveSessionByMac($mac);
        }

        if (! $session) {
            return [];
        }

        $mac = $this->normalizeMac($session['mac-address'] ?? $mac);
        $hostname = null;

        if ($mac) {
            $lease = $routerService->getDhcpLeaseByMac($mac);
            $hostname = $lease['host-name'] ?? null;

            if (! $hostname) {
                $host = $routerService->getHotspotHostByMac($mac);
                $hostname = $host['server-name'] ?? $host['comment'] ?? null;
            }
        }

        return [
            'mac' => $mac,
            'ip' => $session['address'] ?? null,
            'hostname' => $hostname,
            'device_type' => null,
            'device_os' => null,
            'device_browser' => null,
        ];
    }

    /**
     * @param  array<string, ?string>  $profile
     */
    public function persistOnVoucher(Voucher $voucher, array $profile, bool $force = false): Voucher
    {
        $fields = array_filter([
            'activation_mac' => $profile['activation_mac'] ?? null,
            'activation_ip' => $profile['activation_ip'] ?? null,
            'device_type' => $profile['device_type'] ?? null,
            'device_os' => $profile['device_os'] ?? null,
            'device_browser' => $profile['device_browser'] ?? null,
            'device_hostname' => $profile['device_hostname'] ?? null,
        ], fn (?string $value): bool => filled($value));

        if ($force) {
            $voucher->update($fields);

            return $voucher->fresh();
        }

        $updates = [];

        foreach ($fields as $key => $value) {
            if (blank($voucher->{$key})) {
                $updates[$key] = $value;
            }
        }

        if ($updates !== []) {
            $voucher->update($updates);
        }

        return $voucher->fresh();
    }

    public function normalizeMac(?string $mac): ?string
    {
        if (blank($mac)) {
            return null;
        }

        $clean = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $mac) ?? '');

        if (strlen($clean) !== 12) {
            return strtoupper(trim($mac));
        }

        return implode(':', str_split($clean, 2));
    }

    /**
     * @return array{type: string, os: ?string, browser: ?string}
     */
    public function parseUserAgent(?string $userAgent, ?int $screenWidth = null): array
    {
        if (blank($userAgent)) {
            return ['type' => 'unknown', 'os' => null, 'browser' => null];
        }

        $ua = $userAgent;
        $os = $this->detectOs($ua);
        $browser = $this->detectBrowser($ua);
        $type = $this->detectType($ua, $screenWidth);

        return compact('type', 'os', 'browser');
    }

    public function formatDeviceLabel(Voucher $voucher): string
    {
        $parts = array_filter([
            $this->typeLabel($voucher->device_type),
            $voucher->device_os,
            $voucher->device_browser,
            $voucher->device_hostname,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    public function typeLabel(?string $type): ?string
    {
        return match ($type) {
            'phone' => 'Simu',
            'tablet' => 'Tablet',
            'desktop' => 'Kompyuta',
            'laptop' => 'Laptop',
            'tv' => 'TV',
            default => null,
        };
    }

    protected function detectOs(string $ua): ?string
    {
        if (preg_match('/Android ([\d.]+)/', $ua, $m)) {
            return 'Android '.$m[1];
        }

        if (preg_match('/iPhone OS ([\d_]+)/', $ua, $m)) {
            return 'iOS '.str_replace('_', '.', $m[1]);
        }

        if (str_contains($ua, 'iPad')) {
            return 'iPadOS';
        }

        if (preg_match('/Windows NT ([\d.]+)/', $ua, $m)) {
            return 'Windows '.$m[1];
        }

        if (str_contains($ua, 'Mac OS X')) {
            return 'macOS';
        }

        if (str_contains($ua, 'CrOS')) {
            return 'ChromeOS';
        }

        if (str_contains($ua, 'Linux')) {
            return 'Linux';
        }

        return null;
    }

    protected function detectBrowser(string $ua): ?string
    {
        if (preg_match('/EdgA?\/([\d.]+)/', $ua, $m)) {
            return 'Edge '.$m[1];
        }

        if (preg_match('/OPR\/([\d.]+)/', $ua, $m)) {
            return 'Opera '.$m[1];
        }

        if (preg_match('/SamsungBrowser\/([\d.]+)/', $ua, $m)) {
            return 'Samsung Browser '.$m[1];
        }

        if (preg_match('/Chrome\/([\d.]+)/', $ua, $m) && ! str_contains($ua, 'Edg')) {
            return str_contains($ua, 'Mobile') ? 'Chrome Mobile' : 'Chrome '.$m[1];
        }

        if (preg_match('/Firefox\/([\d.]+)/', $ua, $m)) {
            return 'Firefox '.$m[1];
        }

        if (preg_match('/Version\/([\d.]+).*Safari/', $ua, $m) && str_contains($ua, 'Safari')) {
            return str_contains($ua, 'Mobile') ? 'Safari Mobile' : 'Safari '.$m[1];
        }

        return null;
    }

    protected function detectType(string $ua, ?int $screenWidth): string
    {
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'Mobile') || str_contains($ua, 'iPhone') || str_contains($ua, 'Android')) {
            if ($screenWidth !== null && $screenWidth >= 768) {
                return 'tablet';
            }

            return 'phone';
        }

        if (str_contains($ua, 'Smart-TV') || str_contains($ua, 'TV')) {
            return 'tv';
        }

        if (str_contains($ua, 'Windows') || str_contains($ua, 'Macintosh') || str_contains($ua, 'Linux')) {
            return str_contains($ua, 'Mobile') ? 'phone' : 'desktop';
        }

        return 'unknown';
    }
}
