<?php

namespace App\Services;

use App\Models\RouterSetting;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Exceptions\ClientException;
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;
use RuntimeException;

class MikrotikRouterService
{
    protected ?Client $client = null;

    protected ?RouterSetting $settings = null;

    public function connect(): bool
    {
        $settings = $this->getSettings();

        if (! $settings->exists) {
            throw new RuntimeException('Weka taarifa za router kwanza (IP, username na password) kisha hifadhi.');
        }

        if (! $settings->router_ip || ! $settings->router_username || ! $settings->router_password) {
            throw new RuntimeException('Taarifa za router hazijakamilika. Jaza IP, username na password.');
        }

        try {
            $this->client = new Client($this->buildClientConfig($settings));

            $settings->update([
                'is_connected' => true,
                'last_synced_at' => now(),
            ]);

            return true;
        } catch (ConnectException|ClientException $exception) {
            $settings->update(['is_connected' => false]);

            Log::error('MikroTik connection failed', [
                'message' => $exception->getMessage(),
                'router_ip' => $settings->router_ip,
            ]);

            throw new RuntimeException($this->formatConnectionError($settings, $exception->getMessage()), 0, $exception);
        }
    }

    public function disconnect(): void
    {
        $this->client = null;

        if ($this->settings) {
            $this->settings->update(['is_connected' => false]);
        }
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Taarifa za router hazijakamilika. Jaza IP, username na password kisha hifadhi.',
            ];
        }

        try {
            $this->connect();
            $this->client?->query('/system/resource/print')->read();

            return [
                'ok' => true,
                'message' => 'Muunganisho na MikroTik umefanikiwa!',
            ];
        } catch (RuntimeException $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
            ];
        } finally {
            $this->disconnect();
        }
    }

    public function createHotspotUser(
        string $username,
        string $password,
        string $profile,
        ?string $limitUptime = null
    ): array {
        $client = $this->getClient();

        $query = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $username)
            ->equal('password', $password)
            ->equal('profile', $profile);

        if ($limitUptime !== null) {
            $query->equal('limit-uptime', $limitUptime);
        }

        return $client->query($query)->read();
    }

    public function ensureHotspotUser(
        string $username,
        string $password,
        string $profile,
        ?string $limitUptime = null
    ): array {
        $existing = $this->getHotspotUser($username);

        if ($existing) {
            $client = $this->getClient();
            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $existing['.id'])
                ->equal('password', $password)
                ->equal('profile', $profile);

            if ($limitUptime !== null) {
                $query->equal('limit-uptime', $limitUptime);
            }

            return $client->query($query)->read();
        }

        return $this->createHotspotUser($username, $password, $profile, $limitUptime);
    }

    public function deleteHotspotUser(string $username): bool
    {
        $client = $this->getClient();
        $users = $client->query(
            (new Query('/ip/hotspot/user/print'))
                ->where('name', $username)
        )->read();

        if (empty($users)) {
            return false;
        }

        foreach ($users as $user) {
            $client->query(
                (new Query('/ip/hotspot/user/remove'))
                    ->equal('.id', $user['.id'])
            )->read();
        }

        return true;
    }

    public function createUserProfile(string $name, ?string $rateLimit = null, ?string $sessionTimeout = null): array
    {
        $client = $this->getClient();

        $query = (new Query('/ip/hotspot/user/profile/add'))
            ->equal('name', $name);

        if ($rateLimit !== null) {
            $query->equal('rate-limit', $rateLimit);
        }

        if ($sessionTimeout !== null) {
            $query->equal('session-timeout', $sessionTimeout);
        }

        return $client->query($query)->read();
    }

    public function getOnlineUsers(): array
    {
        return $this->getClient()->query(new Query('/ip/hotspot/active/print'))->read();
    }

    public function getActiveSessionForUser(string $username): ?array
    {
        $sessions = $this->getClient()->query(
            (new Query('/ip/hotspot/active/print'))
                ->where('user', $username)
        )->read();

        return $sessions[0] ?? null;
    }

    public function getActiveSessionByMac(string $mac): ?array
    {
        $sessions = $this->getClient()->query(
            (new Query('/ip/hotspot/active/print'))
                ->where('mac-address', $mac)
        )->read();

        return $sessions[0] ?? null;
    }

    public function getDhcpLeaseByMac(string $mac): ?array
    {
        $leases = $this->getClient()->query(
            (new Query('/ip/dhcp-server/lease/print'))
                ->where('mac-address', $mac)
        )->read();

        return $leases[0] ?? null;
    }

    public function getHotspotHostByMac(string $mac): ?array
    {
        $hosts = $this->getClient()->query(
            (new Query('/ip/hotspot/host/print'))
                ->where('mac-address', $mac)
        )->read();

        return $hosts[0] ?? null;
    }

    public function getHotspotHosts(): array
    {
        return $this->getClient()->query(new Query('/ip/hotspot/host/print'))->read();
    }

    public function getHotspotHostByIp(string $ip): ?array
    {
        $hosts = $this->getClient()->query(
            (new Query('/ip/hotspot/host/print'))
                ->where('address', $ip)
        )->read();

        return $hosts[0] ?? null;
    }

    public function getDhcpLeaseByIp(string $ip): ?array
    {
        $leases = $this->getClient()->query(
            (new Query('/ip/dhcp-server/lease/print'))
                ->where('address', $ip)
        )->read();

        return $leases[0] ?? null;
    }

    /**
     * Log a device into the hotspot straight from the API, so internet access
     * works even when the captive portal could not hand us link-login.
     */
    public function loginHotspotUser(string $username, string $password, string $ip, ?string $mac = null): bool
    {
        $query = (new Query('/ip/hotspot/active/login'))
            ->equal('user', $username)
            ->equal('password', $password)
            ->equal('ip', $ip);

        if ($mac) {
            $query->equal('mac-address', $mac);
        }

        try {
            $this->getClient()->query($query)->read();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('MikroTik API hotspot login failed', [
                'user' => $username,
                'ip' => $ip,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getProfileRateLimit(string $profileName): ?string
    {
        $profiles = $this->getClient()->query(
            (new Query('/ip/hotspot/user/profile/print'))
                ->where('name', $profileName)
        )->read();

        return $profiles[0]['rate-limit'] ?? null;
    }

    public function getHotspotUser(string $username): ?array
    {
        $users = $this->getClient()->query(
            (new Query('/ip/hotspot/user/print'))
                ->where('name', $username)
        )->read();

        return $users[0] ?? null;
    }

    public function getAllHotspotUsers(): array
    {
        return $this->getClient()->query(new Query('/ip/hotspot/user/print'))->read();
    }

    /**
     * Prefix for the router-side timers that end a voucher on a calendar
     * deadline. RouterOS 6 only limits time spent online, so a "one day"
     * voucher would otherwise stretch across a week of short visits.
     */
    public const EXPIRY_PREFIX = 'exp-';

    /**
     * Have the router itself drop the account once the paid period is over,
     * so the deadline holds even while the billing server is switched off.
     */
    public function scheduleUserExpiry(string $username, int $seconds): bool
    {
        if ($username === '' || $seconds < 1) {
            return false;
        }

        $client = $this->getClient();
        $name = self::EXPIRY_PREFIX.$username;

        $this->removeUserExpiry($username);

        $onEvent = '/ip hotspot active remove [find user="'.$username.'"]; '
            .'/ip hotspot user remove [find name="'.$username.'"]; '
            .'/system scheduler remove [find name="'.$name.'"]';

        $client->query(
            (new Query('/system/scheduler/add'))
                ->equal('name', $name)
                ->equal('interval', $this->formatInterval($seconds))
                ->equal('on-event', $onEvent)
                ->equal('comment', 'muda wa vocha')
        )->read();

        return true;
    }

    public function removeUserExpiry(string $username): bool
    {
        $client = $this->getClient();

        $existing = $client->query(
            (new Query('/system/scheduler/print'))
                ->where('name', self::EXPIRY_PREFIX.$username)
        )->read();

        foreach ($existing as $row) {
            if (isset($row['.id'])) {
                $client->query(
                    (new Query('/system/scheduler/remove'))
                        ->equal('.id', $row['.id'])
                )->read();
            }
        }

        return $existing !== [];
    }

    /**
     * @return array<string, array<string, string>> keyed by the account name
     */
    public function getUserExpiries(): array
    {
        $schedules = $this->getClient()->query(new Query('/system/scheduler/print'))->read();
        $expiries = [];

        foreach ($schedules as $row) {
            $name = (string) ($row['name'] ?? '');

            if (! str_starts_with($name, self::EXPIRY_PREFIX)) {
                continue;
            }

            $expiries[substr($name, strlen(self::EXPIRY_PREFIX))] = $row;
        }

        return $expiries;
    }

    /**
     * RouterOS wants an interval as `[Nd ]HH:MM:SS`, and it refuses anything
     * that leaves the hour field above 23.
     */
    protected function formatInterval(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $remainder = $seconds % 86400;

        $clock = sprintf(
            '%02d:%02d:%02d',
            intdiv($remainder, 3600),
            intdiv($remainder % 3600, 60),
            $remainder % 60
        );

        return $days > 0 ? $days.'d '.$clock : $clock;
    }

    public function getOfflineUsers(): array
    {
        $allUsers = $this->getAllHotspotUsers();
        $onlineUsers = collect($this->getOnlineUsers())->pluck('user')->filter()->all();

        return array_values(array_filter(
            $allUsers,
            fn (array $user): bool => ! in_array($user['name'] ?? '', $onlineUsers, true)
        ));
    }

    public function disconnectUser(string $username): bool
    {
        $client = $this->getClient();
        $sessions = $client->query(
            (new Query('/ip/hotspot/active/print'))
                ->where('user', $username)
        )->read();

        if (empty($sessions)) {
            return false;
        }

        foreach ($sessions as $session) {
            $client->query(
                (new Query('/ip/hotspot/active/remove'))
                    ->equal('.id', $session['.id'])
            )->read();
        }

        return true;
    }

    public function syncActiveUsers(): int
    {
        $deviceDetection = app(DeviceDetectionService::class);
        $sessions = collect($this->getOnlineUsers());
        $synced = 0;

        foreach ($sessions as $session) {
            $username = $session['user'] ?? null;

            if (! $username) {
                continue;
            }

            $voucher = Voucher::query()
                ->where('status', Voucher::STATUS_USED)
                ->where(function ($query) use ($username): void {
                    $query->where('mikrotik_username', $username)
                        ->orWhere('code', $username);
                })
                ->first();

            if (! $voucher) {
                continue;
            }

            $routerProfile = [
                'mac' => $session['mac-address'] ?? null,
                'ip' => $session['address'] ?? null,
                'hostname' => null,
                'device_type' => null,
                'device_os' => null,
                'device_browser' => null,
            ];

            $mac = $deviceDetection->normalizeMac($routerProfile['mac']);

            if ($mac) {
                $lease = $this->getDhcpLeaseByMac($mac);
                $routerProfile['hostname'] = $lease['host-name'] ?? null;

                if (! $routerProfile['hostname']) {
                    $host = $this->getHotspotHostByMac($mac);
                    $routerProfile['hostname'] = $host['server-name'] ?? $host['comment'] ?? null;
                }
            }

            $merged = $deviceDetection->mergeProfiles([
                'activation_mac' => $voucher->activation_mac,
                'activation_ip' => $voucher->activation_ip,
                'device_type' => $voucher->device_type,
                'device_os' => $voucher->device_os,
                'device_browser' => $voucher->device_browser,
                'device_hostname' => $voucher->device_hostname,
            ], $routerProfile);

            $updates = array_filter([
                'activation_mac' => $merged['activation_mac'],
                'activation_ip' => $merged['activation_ip'],
                'device_type' => $merged['device_type'],
                'device_os' => $merged['device_os'],
                'device_browser' => $merged['device_browser'],
                'device_hostname' => $merged['device_hostname'],
                'activated_at' => $voucher->activated_at ?? now(),
            ], fn ($value, string $key): bool => $key === 'activated_at' || (filled($value) && blank($voucher->{$key})), ARRAY_FILTER_USE_BOTH);

            if ($updates !== []) {
                $voucher->update($updates);
                $synced++;
            }
        }

        $this->getSettings()->update(['last_synced_at' => now()]);

        return $synced;
    }

    public function syncExpiredUsers(): int
    {
        $expiredCount = Voucher::query()
            ->where('status', Voucher::STATUS_USED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => Voucher::STATUS_EXPIRED]);

        $onlineUsers = collect($this->getOnlineUsers())
            ->pluck('user')
            ->filter()
            ->all();

        $usedVouchers = Voucher::query()
            ->where('status', Voucher::STATUS_USED)
            ->whereNotNull('mikrotik_username')
            ->get();

        foreach ($usedVouchers as $voucher) {
            if (! in_array($voucher->mikrotik_username, $onlineUsers, true)) {
                continue;
            }

            if ($voucher->expires_at && $voucher->expires_at->isPast()) {
                $this->deleteHotspotUser($voucher->mikrotik_username);
                $voucher->update(['status' => Voucher::STATUS_EXPIRED]);
                $expiredCount++;
            }
        }

        $this->getSettings()->update(['last_synced_at' => now()]);

        return $expiredCount;
    }

    public function isConfigured(): bool
    {
        $settings = $this->getSettings();

        return filled($settings->router_ip)
            && filled($settings->router_username)
            && filled($settings->router_password);
    }

    protected function getClient(): Client
    {
        if ($this->client === null) {
            $this->connect();
        }

        return $this->client;
    }

    protected function buildClientConfig(RouterSetting $settings): array
    {
        $config = [
            'host' => $settings->router_ip,
            'port' => $settings->router_port ?: 8728,
            'user' => $settings->router_username,
            'pass' => $settings->router_password,
            'timeout' => 5,
            'attempts' => 2,
            'delay' => 1,
        ];

        $bindIp = $this->resolveLocalBindIp($settings->router_ip);

        if ($bindIp) {
            $config['socket_options'] = ['bindto' => $bindIp.':0'];
        }

        return $config;
    }

    protected function resolveLocalBindIp(string $routerIp): ?string
    {
        $configured = config('services.mikrotik.bind_ip');

        if (filled($configured)) {
            return $configured;
        }

        $parts = explode('.', $routerIp);

        if (count($parts) !== 4) {
            return null;
        }

        $subnetPrefix = "{$parts[0]}.{$parts[1]}.{$parts[2]}.";

        $hostname = gethostname();

        if (! $hostname) {
            return null;
        }

        $addresses = gethostbynamel($hostname) ?: [];

        foreach ($addresses as $address) {
            if ($address === $routerIp) {
                continue;
            }

            if (str_starts_with($address, $subnetPrefix)) {
                return $address;
            }
        }

        return null;
    }

    protected function getSettings(): RouterSetting
    {
        if ($this->settings === null) {
            $this->settings = RouterSetting::current() ?? RouterSetting::resolve();
        }

        return $this->settings;
    }

    protected function formatConnectionError(RouterSetting $settings, string $message): string
    {
        $port = $settings->router_port ?: 8728;

        if (str_contains(strtolower($message), 'invalid user') || str_contains(strtolower($message), 'password')) {
            return 'Username au password si sahihi. Tumia username ya MikroTik (mfano: admin), si email ya mfumo wako.';
        }

        if (str_contains($message, 'actively refused')) {
            return "Imeshindwa kuunganisha na {$settings->router_ip}:{$port}. API haijawashwa au port si sahihi. Kwenye MikroTik enda IP → Services → api → iwe enabled.";
        }

        if (str_contains($message, 'failed to respond') || str_contains($message, 'timed out')) {
            $bindIp = $this->resolveLocalBindIp($settings->router_ip);

            return "Imeshindwa kufikia {$settings->router_ip}:{$port}. PC yako inaweza kutumia network isiyo sahihi."
                .($bindIp ? " Jaribu kuweka MIKROTIK_BIND_IP={$bindIp} kwenye .env." : '')
                .' Hakikisha API imewashwa kwenye MikroTik (IP → Services → api).';
        }

        return 'Imeshindwa kuunganisha na MikroTik: '.$message;
    }
}
