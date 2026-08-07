<?php

namespace App\Console\Commands;

use App\Services\CustomerMonitoringService;
use Illuminate\Console\Command;

class SyncCustomerServiceStatusCommand extends Command
{
    protected $signature = 'customers:sync-service-status';

    protected $description = 'Assess online status and internet quality for active customers';

    public function handle(CustomerMonitoringService $monitoringService): int
    {
        $result = $monitoringService->syncAllCustomers();

        $this->info("Customers assessed: {$result['assessed']}");
        $this->info("Currently online: {$result['online']}");

        return self::SUCCESS;
    }
}
