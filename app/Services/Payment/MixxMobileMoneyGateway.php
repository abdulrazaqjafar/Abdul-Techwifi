<?php

namespace App\Services\Payment;

class MixxMobileMoneyGateway extends AbstractProviderGateway
{
    public function providerName(): string
    {
        return 'mixx';
    }

    protected function providerLabel(): string
    {
        return 'Mixx by Yas';
    }
}
