<?php

namespace App\Services\Payment;

use App\Contracts\MobileMoneyGatewayInterface;
use App\Models\HotspotTransaction;
use RuntimeException;

abstract class AbstractProviderGateway implements MobileMoneyGatewayInterface
{
    public function initiate(HotspotTransaction $transaction): array
    {
        throw new RuntimeException(
            sprintf('%s integration is not configured. Set HOTSPOT_PAYMENT_PROVIDER=simulated for development.', $this->providerLabel())
        );
    }

    public function checkStatus(HotspotTransaction $transaction): array
    {
        throw new RuntimeException(sprintf('%s integration is not configured.', $this->providerLabel()));
    }

    abstract protected function providerLabel(): string;
}
