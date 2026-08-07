<?php

namespace App\Services\Payment;

class MpesaMobileMoneyGateway extends AbstractProviderGateway
{
    public function providerName(): string
    {
        return 'mpesa';
    }

    protected function providerLabel(): string
    {
        return 'M-Pesa';
    }
}
