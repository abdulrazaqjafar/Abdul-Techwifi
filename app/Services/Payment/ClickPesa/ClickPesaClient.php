<?php

namespace App\Services\Payment\ClickPesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class ClickPesaClient
{
    protected string $baseUrl;

    protected string $clientId;

    protected string $apiKey;

    protected string $checksumKey;

    protected bool $checksumEnabled;

    protected bool $sandbox;

    public function __construct()
    {
        $config = config('hotspot.providers.clickpesa', []);

        $this->sandbox = (bool) ($config['sandbox'] ?? false);
        $defaultBaseUrl = $this->sandbox
            ? 'https://api-sandbox.clickpesa.com/third-parties'
            : 'https://api.clickpesa.com/third-parties';

        $this->baseUrl = rtrim($config['base_url'] ?? $defaultBaseUrl, '/');
        $this->clientId = (string) ($config['client_id'] ?? '');
        $this->apiKey = (string) ($config['api_key'] ?? '');
        $this->checksumKey = (string) ($config['checksum_key'] ?? $this->apiKey);
        $this->checksumEnabled = (bool) ($config['checksum_enabled'] ?? true);
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->apiKey !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiateUssdPush(array $payload): array
    {
        $this->ensureConfigured();

        if ($this->checksumEnabled) {
            $payload['checksum'] = $this->createPayloadChecksum($payload);
        }

        $response = $this->httpClient([
            'Authorization' => $this->authorizationHeader(),
        ])->post("{$this->baseUrl}/payments/initiate-ussd-push-request", $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response->json(), 'Failed to initiate ClickPesa payment.', $response->status()));
        }

        return $response->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function queryPaymentStatus(string $orderReference): array
    {
        $this->ensureConfigured();

        $response = $this->httpClient([
            'Authorization' => $this->authorizationHeader(),
        ])->get("{$this->baseUrl}/payments/{$orderReference}");

        if ($response->status() === 404) {
            return [];
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response->json(), 'Failed to query ClickPesa payment status.', $response->status()));
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function previewUssdPush(array $payload): array
    {
        $this->ensureConfigured();

        if ($this->checksumEnabled) {
            $payload['checksum'] = $this->createPayloadChecksum($payload);
        }

        $response = $this->httpClient([
            'Authorization' => $this->authorizationHeader(),
        ])->post("{$this->baseUrl}/payments/preview-ussd-push-request", $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response->json(), 'Failed to preview ClickPesa payment.', $response->status()));
        }

        return $response->json();
    }

    /**
     * @return array{success: bool, environment: string, base_url: string, token: string}
     */
    public function generateToken(bool $forceRefresh = false): array
    {
        $this->ensureConfigured();

        if ($forceRefresh) {
            $this->clearTokenCache();
        }

        $response = $this->httpClient([
            'client-id' => $this->clientId,
            'api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/generate-token");

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrorMessage($response->json(), 'Failed to authenticate with ClickPesa.', $response->status()));
        }

        $body = $response->json();
        $token = $body['token'] ?? $body['accessToken'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('ClickPesa did not return an access token.');
        }

        $cacheKey = $this->tokenCacheKey();
        Cache::put($cacheKey, $token, now()->addMinutes(55));

        return [
            'success' => (bool) ($body['success'] ?? true),
            'environment' => $this->sandbox ? 'sandbox' : 'production',
            'base_url' => $this->baseUrl,
            'token' => $token,
        ];
    }

    public function getToken(bool $forceRefresh = false): string
    {
        if ($forceRefresh) {
            return $this->generateToken(true)['token'];
        }

        return $this->getAccessToken();
    }

    public function clearTokenCache(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    /**
     * @return array{success: bool, environment: string, base_url: string}
     */
    public function testConnection(): array
    {
        $result = $this->generateToken();

        return [
            'success' => $result['success'],
            'environment' => $result['environment'],
            'base_url' => $result['base_url'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyPayloadChecksum(array $payload, string $receivedChecksum): bool
    {
        if ($this->checksumKey === '') {
            return false;
        }

        unset($payload['checksum'], $payload['checksumMethod']);

        return hash_equals($this->createPayloadChecksum($payload), $receivedChecksum);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createPayloadChecksum(array $payload): string
    {
        $canonicalPayload = $this->canonicalize($payload);
        $payloadString = json_encode($canonicalPayload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payloadString, $this->checksumKey);
    }

    public function sanitizeOrderReference(string $reference): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $reference) ?: $reference;
    }

    protected function getAccessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), now()->addMinutes(55), function (): string {
            return $this->generateToken()['token'];
        });
    }

    protected function authorizationHeader(): string
    {
        $token = $this->getAccessToken();

        if (str_starts_with($token, 'Bearer ')) {
            return $token;
        }

        return 'Bearer '.$token;
    }

    protected function tokenCacheKey(): string
    {
        return 'clickpesa_access_token_'.($this->sandbox ? 'sandbox' : 'live');
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    protected function canonicalize($value)
    {
        if ($value === null || ! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }

        ksort($value);

        $result = [];

        foreach ($value as $key => $item) {
            $result[$key] = $this->canonicalize($item);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    protected function extractErrorMessage(?array $body, string $fallback, ?int $status = null): string
    {
        if (! is_array($body)) {
            return $status ? "{$fallback} (HTTP {$status})" : $fallback;
        }

        $message = $body['message'] ?? $body['error'] ?? null;

        if (is_string($message) && $message !== '') {
            return $status ? "{$message} (HTTP {$status})" : $message;
        }

        return $status ? "{$fallback} (HTTP {$status})" : $fallback;
    }

    protected function httpClient(array $headers = []): PendingRequest
    {
        return Http::withHeaders($headers)
            ->acceptJson()
            ->timeout(30)
            // Repeating a request ClickPesa already answered does not change
            // the answer, it only spends the daily API quota three times over.
            // Passing throw: false limits retries to connection faults.
            ->retry(3, 1000, null, false);
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ClickPesa credentials are not configured. Set CLICKPESA_CLIENT_ID and CLICKPESA_API_KEY in your .env file.');
        }
    }
}
