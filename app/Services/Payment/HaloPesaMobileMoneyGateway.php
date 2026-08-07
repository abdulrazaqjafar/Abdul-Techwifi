<?php

namespace App\Services\Payment;

class HaloPesaMobileMoneyGateway extends AbstractProviderGateway
{
    public function providerName(): string
    {
        return 'halopesa';
    }

    protected function providerLabel(): string
    {
        return 'HaloPesa';
    }
}
