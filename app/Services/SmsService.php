<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    public function send(string $phone, string $message, ?Customer $customer = null): SmsLog
    {
        $settings = BusinessSetting::getSettings();
        $provider = $settings->sms_provider ?: config('services.sms.default', 'log');
        $normalizedPhone = $this->normalizePhone($phone);

        $log = SmsLog::query()->create([
            'customer_id' => $customer?->id,
            'phone' => $normalizedPhone,
            'message' => $message,
            'provider' => $provider,
            'status' => 'pending',
        ]);

        try {
            $response = match ($provider) {
                'africastalking' => $this->sendViaAfricasTalking($normalizedPhone, $message, $settings->sms_sender_id),
                default => $this->sendViaLog($normalizedPhone, $message),
            };

            $log->update([
                'status' => 'sent',
                'response' => is_string($response) ? $response : json_encode($response),
            ]);
        } catch (RuntimeException $exception) {
            $log->update([
                'status' => 'failed',
                'response' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $log->fresh();
    }

    protected function sendViaLog(string $phone, string $message): string
    {
        Log::info('SMS (log driver)', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return 'Logged locally — configure Africa\'s Talking for live SMS.';
    }

    /**
     * @return array<string, mixed>|string
     */
    protected function sendViaAfricasTalking(string $phone, string $message, ?string $senderId): array|string
    {
        $username = config('services.africastalking.username');
        $apiKey = config('services.africastalking.api_key');
        $from = $senderId ?: config('services.africastalking.from');

        if (! $username || ! $apiKey) {
            throw new RuntimeException('Africa\'s Talking SMS credentials are not configured.');
        }

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $username,
            'to' => '+'.$phone,
            'message' => $message,
            'from' => $from,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('SMS failed: '.$response->body());
        }

        return $response->json() ?? $response->body();
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        return $digits;
    }
}
