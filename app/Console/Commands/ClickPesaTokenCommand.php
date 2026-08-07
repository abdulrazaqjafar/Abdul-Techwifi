<?php

namespace App\Console\Commands;

use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ClickPesaTokenCommand extends Command
{
    protected $signature = 'clickpesa:token {--fresh : Pata token mpya bila kutumia cache}';

    protected $description = 'Pata JWT Token kutoka ClickPesa API';

    public function handle(ClickPesaClient $client): int
    {
        $this->info('ClickPesa JWT Token');
        $this->newLine();

        if (! $client->isConfigured()) {
            $this->error('Weka CLICKPESA_CLIENT_ID na CLICKPESA_API_KEY kwenye .env');

            return self::FAILURE;
        }

        $this->line('Environment: '.($client->isSandbox() ? 'Sandbox' : 'Production'));
        $this->line('Base URL: '.$client->getBaseUrl());
        $this->newLine();

        if ($this->option('fresh')) {
            $cacheKey = 'clickpesa_access_token_'.($client->isSandbox() ? 'sandbox' : 'live');
            cache()->forget($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'client-id' => config('hotspot.providers.clickpesa.client_id'),
                'api-key' => config('hotspot.providers.clickpesa.api_key'),
            ])->acceptJson()->post($client->getBaseUrl().'/generate-token');

            if (! $response->successful()) {
                $body = $response->json();
                $this->error('Imeshindikana (HTTP '.$response->status().')');
                $this->line($body['message'] ?? $response->body());

                return self::FAILURE;
            }

            $body = $response->json();
            $token = $body['token'] ?? $body['accessToken'] ?? null;

            if (! is_string($token) || $token === '') {
                $this->error('ClickPesa haikurudisha token.');
                $this->line(json_encode($body, JSON_PRETTY_PRINT));

                return self::FAILURE;
            }

            $this->info('JWT Token imepatikana kikamilifu!');
            $this->newLine();
            $this->line('<fg=gray>Success:</> '.($body['success'] ?? 'true'));
            $this->line('<fg=gray>Valid for:</> ~60 minutes (1 hour)');
            $this->newLine();
            $this->line('<fg=yellow>Token:</>');
            $this->line($token);
            $this->newLine();
            $this->comment('Tumia header: Authorization: Bearer {token}');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
