<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerServiceSnapshot;
use App\Models\Voucher;
use RuntimeException;

class CustomerMonitoringService
{
    public function __construct(
        protected MikrotikRouterService $routerService,
        protected DeviceDetectionService $deviceDetection
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function assessCustomer(Customer $customer, bool $persist = true): array
    {
        $voucher = $customer->activeVoucher();

        if (! $voucher) {
            return $this->buildResult($customer, null, [
                'is_online' => false,
                'quality_score' => 'inactive',
                'quality_label' => 'Hakuna huduma hai',
                'notes' => 'Mteja hana voucher inayotumika kwa sasa.',
            ], $persist);
        }

        if (! $this->routerService->isConfigured()) {
            return $this->buildResult($customer, $voucher, [
                'is_online' => false,
                'quality_score' => 'unknown',
                'quality_label' => 'Router haijasanidiwa',
                'notes' => 'Sanidi MikroTik kwenye Settings ili kupima speed.',
            ], $persist);
        }

        try {
            $session = $this->routerService->getActiveSessionForUser($voucher->mikrotik_username);
            $profile = $voucher->package?->profile_name
                ? $this->routerService->getProfileRateLimit($voucher->package->profile_name)
                : null;

            $rateLimit = $session['rate-limit'] ?? $profile ?? null;
            $isOnline = $session !== null;
            $bytesIn = (int) ($session['bytes-in'] ?? 0);
            $bytesOut = (int) ($session['bytes-out'] ?? 0);
            $uptimeSeconds = $this->parseUptimeToSeconds($session['uptime'] ?? '0s');

            [$score, $label, $notes] = $this->scoreQuality($isOnline, $rateLimit, $bytesIn + $bytesOut, $uptimeSeconds);

            return $this->buildResult($customer, $voucher, [
                'is_online' => $isOnline,
                'rate_limit' => $rateLimit,
                'bytes_in' => $bytesIn,
                'bytes_out' => $bytesOut,
                'uptime_seconds' => $uptimeSeconds,
                'quality_score' => $score,
                'quality_label' => $label,
                'notes' => $notes,
                'ip_address' => $session['address'] ?? null,
                'mac_address' => $session['mac-address'] ?? null,
            ], $persist);
        } catch (RuntimeException $exception) {
            return $this->buildResult($customer, $voucher, [
                'is_online' => false,
                'quality_score' => 'unknown',
                'quality_label' => 'Imeshindikana kupima',
                'notes' => $exception->getMessage(),
            ], $persist);
        } finally {
            $this->routerService->disconnect();
        }
    }

    /**
     * @return array{assessed: int, online: int}
     */
    public function syncAllCustomers(): array
    {
        $assessed = 0;
        $online = 0;

        Customer::query()
            ->whereHas('vouchers', function ($query): void {
                $query->where('status', Voucher::STATUS_USED)
                    ->whereNotNull('mikrotik_username');
            })
            ->chunkById(25, function ($customers) use (&$assessed, &$online): void {
                foreach ($customers as $customer) {
                    $result = $this->assessCustomer($customer);
                    $assessed++;

                    if ($result['is_online']) {
                        $online++;
                    }
                }
            });

        return compact('assessed', 'online');
    }

    /**
     * @return array{users: list<array<string, mixed>>, summary: array{good: int, fair: int, poor: int, unknown: int}}
     */
    public function assessActiveSessions(): array
    {
        $summary = ['good' => 0, 'fair' => 0, 'poor' => 0, 'unknown' => 0];
        $users = [];

        if (! $this->routerService->isConfigured()) {
            return compact('users', 'summary');
        }

        try {
            $this->routerService->connect();
            $sessions = $this->routerService->getOnlineUsers();

            $vouchersByUsername = Voucher::query()
                ->with(['package:id,name,profile_name', 'customer:id,name'])
                ->where('status', Voucher::STATUS_USED)
                ->whereNotNull('mikrotik_username')
                ->get()
                ->keyBy('mikrotik_username');

            $vouchersByCode = Voucher::query()
                ->with(['package:id,name,profile_name', 'customer:id,name'])
                ->where('status', Voucher::STATUS_USED)
                ->get()
                ->keyBy('code');

            foreach ($sessions as $session) {
                $username = $session['user'] ?? null;

                if (! $username) {
                    continue;
                }

                $voucher = $vouchersByUsername->get($username) ?? $vouchersByCode->get($username);
                $profile = $voucher?->package?->profile_name
                    ? $this->routerService->getProfileRateLimit($voucher->package->profile_name)
                    : null;

                $rateLimit = $session['rate-limit'] ?? $profile ?? null;
                $bytesIn = (int) ($session['bytes-in'] ?? 0);
                $bytesOut = (int) ($session['bytes-out'] ?? 0);
                $uptimeSeconds = $this->parseUptimeToSeconds($session['uptime'] ?? '0s');

                [$score, $label] = $this->scoreQuality(true, $rateLimit, $bytesIn + $bytesOut, $uptimeSeconds);

                if (isset($summary[$score])) {
                    $summary[$score]++;
                } else {
                    $summary['unknown']++;
                }

                $users[] = [
                    'username' => $username,
                    'voucher_code' => $voucher?->code,
                    'customer' => $voucher?->customer?->name,
                    'package' => $voucher?->package?->name,
                    'source' => $voucher?->source,
                    'mac' => $session['mac-address'] ?? $voucher?->activation_mac,
                    'ip' => $session['address'] ?? $voucher?->activation_ip,
                    'device' => $voucher ? $this->deviceDetection->formatDeviceLabel($voucher) : '—',
                    'device_type' => $voucher?->device_type,
                    'device_os' => $voucher?->device_os,
                    'device_browser' => $voucher?->device_browser,
                    'device_hostname' => $voucher?->device_hostname,
                    'quality_score' => $score,
                    'quality_label' => $label,
                    'rate_limit' => $rateLimit,
                    'uptime' => $session['uptime'] ?? null,
                    'bytes_total' => $bytesIn + $bytesOut,
                ];
            }

            usort($users, function (array $a, array $b): int {
                $priority = ['poor' => 0, 'fair' => 1, 'good' => 2];

                return ($priority[$a['quality_score']] ?? 3) <=> ($priority[$b['quality_score']] ?? 3);
            });
        } catch (RuntimeException) {
            // Router unreachable — return empty snapshot.
        } finally {
            $this->routerService->disconnect();
        }

        return compact('users', 'summary');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildResult(Customer $customer, ?Voucher $voucher, array $data, bool $persist): array
    {
        $result = array_merge([
            'customer_id' => $customer->id,
            'voucher_id' => $voucher?->id,
            'mikrotik_username' => $voucher?->mikrotik_username,
            'is_online' => false,
            'rate_limit' => null,
            'bytes_in' => 0,
            'bytes_out' => 0,
            'uptime_seconds' => 0,
            'quality_score' => 'unknown',
            'quality_label' => 'Haijulikani',
            'notes' => null,
            'ip_address' => null,
            'mac_address' => null,
            'recorded_at' => now(),
        ], $data);

        if ($persist) {
            CustomerServiceSnapshot::query()->create([
                'customer_id' => $customer->id,
                'voucher_id' => $voucher?->id,
                'mikrotik_username' => $voucher?->mikrotik_username,
                'ip_address' => $result['ip_address'],
                'mac_address' => $result['mac_address'] ?? null,
                'is_online' => (bool) $result['is_online'],
                'rate_limit' => $result['rate_limit'],
                'bytes_in' => (int) $result['bytes_in'],
                'bytes_out' => (int) $result['bytes_out'],
                'uptime_seconds' => (int) $result['uptime_seconds'],
                'quality_score' => (string) $result['quality_score'],
                'quality_label' => (string) $result['quality_label'],
                'notes' => $result['notes'],
                'recorded_at' => now(),
            ]);
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    protected function scoreQuality(bool $isOnline, ?string $rateLimit, int $totalBytes, int $uptimeSeconds): array
    {
        if (! $isOnline) {
            return ['offline', 'Nje ya mtandao', 'Voucher ipo lakini mteja hayupo online sasa hivi.'];
        }

        $downloadMbps = $this->estimateMbpsFromRateLimit($rateLimit);

        if ($downloadMbps >= 5) {
            return ['good', 'Huduma Nzuri', 'Speed ya internet inaonekana ya kuridhisha ('.($rateLimit ?: 'profile haijulikani').').'];
        }

        if ($downloadMbps >= 2 || $totalBytes > 0) {
            return ['fair', 'Huduma Wastani', 'Mteja yupo online. Speed ni wastani — fuatilia ikiendelea.'];
        }

        return ['poor', 'Huduma Dhaifu', 'Mteja yupo online lakini matumizi ya data ni madogo au speed ni chini.'];
    }

    protected function estimateMbpsFromRateLimit(?string $rateLimit): float
    {
        if (! $rateLimit) {
            return 0;
        }

        if (preg_match('/(\d+)M/i', $rateLimit, $matches)) {
            return (float) $matches[1];
        }

        if (preg_match('/(\d+)k/i', $rateLimit, $matches)) {
            return ((float) $matches[1]) / 1000;
        }

        return 0;
    }

    protected function parseUptimeToSeconds(string $uptime): int
    {
        $seconds = 0;

        if (preg_match('/(\d+)w/', $uptime, $m)) {
            $seconds += (int) $m[1] * 604800;
        }
        if (preg_match('/(\d+)d/', $uptime, $m)) {
            $seconds += (int) $m[1] * 86400;
        }
        if (preg_match('/(\d+)h/', $uptime, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)m/', $uptime, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)s/', $uptime, $m)) {
            $seconds += (int) $m[1];
        }

        return $seconds;
    }
}
