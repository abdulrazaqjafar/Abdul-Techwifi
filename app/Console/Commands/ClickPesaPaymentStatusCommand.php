<?php

namespace App\Console\Commands;

use App\Services\Payment\ClickPesa\ClickPesaClient;
use Illuminate\Console\Command;

class ClickPesaPaymentStatusCommand extends Command
{
    protected $signature = 'clickpesa:status {reference : Order reference ya malipo}';

    protected $description = 'Angalia hali ya malipo ya ClickPesa';

    public function handle(ClickPesaClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('Credentials hazijawekwa kwenye .env');

            return self::FAILURE;
        }

        $reference = $client->sanitizeOrderReference($this->argument('reference'));

        try {
            $payments = $client->queryPaymentStatus($reference);

            if ($payments === []) {
                $this->warn("Hakuna malipo yaliyopatikana kwa reference: {$reference}");

                return self::SUCCESS;
            }

            $this->table(
                ['ID', 'Status', 'Channel', 'Amount', 'Updated'],
                collect($payments)->map(fn (array $payment): array => [
                    $payment['id'] ?? '-',
                    $payment['status'] ?? '-',
                    $payment['channel'] ?? '-',
                    ($payment['collectedAmount'] ?? '-').' '.($payment['collectedCurrency'] ?? ''),
                    $payment['updatedAt'] ?? $payment['createdAt'] ?? '-',
                ])->all()
            );

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
