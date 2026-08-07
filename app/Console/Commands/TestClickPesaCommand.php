<?php

namespace App\Console\Commands;

use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestClickPesaCommand extends Command
{
    protected $signature = 'clickpesa:test
                            {--phone=255712345678 : Nambari ya simu ya majaribio (255XXXXXXXXX)}
                            {--amount=500 : Kiasi cha malipo kwa majaribio}
                            {--preview : Angalia njia za malipo tu, usitume ombi}';

    protected $description = 'Jaribu muunganisho wa ClickPesa sandbox';

    public function handle(ClickPesaClient $client): int
    {
        $this->info('ClickPesa Sandbox — Majaribio');
        $this->newLine();

        if (! $client->isConfigured()) {
            $this->error('Credentials hazijawekwa.');
            $this->line('Weka CLICKPESA_CLIENT_ID na CLICKPESA_API_KEY kwenye .env yako.');
            $this->line('Pata credentials kutoka: https://merchant.clickpesa.com → Settings → Developers');
            $this->newLine();
            $this->comment('Kwa sasa, mfumo utatumia simulated gateway kwenye localhost bila credentials.');

            return self::FAILURE;
        }

        $this->line('Environment: '.($client->isSandbox() ? 'Sandbox (Majaribio)' : 'Production'));
        $this->line('Base URL: '.$client->getBaseUrl());
        $this->newLine();

        try {
            $result = $client->testConnection();
            $this->info('Muunganisho umefanikiwa — token imepatikana.');
        } catch (\Throwable $exception) {
            $this->error('Muunganisho umeshindikana: '.$exception->getMessage());

            return self::FAILURE;
        }

        $phone = preg_replace('/\D/', '', (string) $this->option('phone')) ?? '';
        $amount = (string) (int) $this->option('amount');
        $orderReference = 'TST'.strtoupper(Str::random(8));

        $payload = [
            'amount' => $amount,
            'currency' => 'TZS',
            'orderReference' => $orderReference,
            'phoneNumber' => $phone,
            'fetchSenderDetails' => true,
        ];

        try {
            if ($this->option('preview')) {
                $this->info('Inaangalia njia za malipo...');
                $preview = $client->previewUssdPush($payload);
                $this->table(
                    ['Njia', 'Hali', 'Ada'],
                    collect($preview['activeMethods'] ?? [])->map(fn (array $method): array => [
                        $method['name'] ?? '-',
                        $method['status'] ?? '-',
                        isset($method['fee']) ? number_format((float) $method['fee']).' TZS' : '-',
                    ])->all()
                );

                if (isset($preview['sender'])) {
                    $this->line('Mtu: '.($preview['sender']['accountName'] ?? '-'));
                    $this->line('Mtandao: '.($preview['sender']['accountProvider'] ?? '-'));
                }

                return self::SUCCESS;
            }

            if (! $this->confirm("Tuma ombi la malipo la majaribio la {$amount} TZS kwenda {$phone}?", true)) {
                $this->comment('Imesitishwa.');

                return self::SUCCESS;
            }

            $this->info('Inatuma USSD Push...');
            $response = $client->initiateUssdPush([
                'amount' => $amount,
                'currency' => 'TZS',
                'orderReference' => $orderReference,
                'phoneNumber' => $phone,
            ]);

            $this->table(
                ['Field', 'Value'],
                [
                    ['Transaction ID', $response['id'] ?? '-'],
                    ['Status', $response['status'] ?? '-'],
                    ['Channel', $response['channel'] ?? '-'],
                    ['Order Reference', $response['orderReference'] ?? $orderReference],
                    ['Amount', ($response['collectedAmount'] ?? $amount).' '.($response['collectedCurrency'] ?? 'TZS')],
                ]
            );

            $this->newLine();
            $this->comment("Angalia hali: php artisan clickpesa:status {$orderReference}");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Jaribio limeshindikana: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
