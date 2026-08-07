<?php

namespace App\Console\Commands;

use App\Services\MonthlyOfferService;
use Illuminate\Console\Command;

class SendMonthlyOffersCommand extends Command
{
    protected $signature = 'offers:send-monthly {--force : Run even if today is not the penultimate day of the month}';

    protected $description = 'Generate and SMS monthly offers to customers on the penultimate day of each month';

    public function handle(MonthlyOfferService $monthlyOfferService): int
    {
        if (! $this->option('force') && ! MonthlyOfferService::isPenultimateDayOfMonth()) {
            $this->info('Leo si siku ya pili ya mwisho wa mwezi. Hakuna offer zilizotumwa.');

            return self::SUCCESS;
        }

        $result = $monthlyOfferService->dispatchMonthlyOffers(force: (bool) $this->option('force'));

        $this->info("Offers created: {$result['created']}");
        $this->info("SMS sent: {$result['sent']}");
        $this->info("Skipped: {$result['skipped']}");

        return self::SUCCESS;
    }
}
