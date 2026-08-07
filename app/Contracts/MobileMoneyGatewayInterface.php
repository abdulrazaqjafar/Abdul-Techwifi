<?php

namespace App\Contracts;

use App\Models\HotspotTransaction;

interface MobileMoneyGatewayInterface
{
    public function initiate(HotspotTransaction $transaction): array;

    public function checkStatus(HotspotTransaction $transaction): array;

    public function providerName(): string;
}
