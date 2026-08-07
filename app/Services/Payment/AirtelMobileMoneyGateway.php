<?php

namespace App\Services\Payment;

class AirtelMobileMoneyGateway extends AbstractProviderGateway
{
    public function providerName(): string
    {
        return 'airtel';
    }

    protected function providerLabel(): string
    {
        return 'Airtel Money';
    }
}
