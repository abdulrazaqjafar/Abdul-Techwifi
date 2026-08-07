<?php

namespace App\Services\Payment;

use App\Contracts\MobileMoneyGatewayInterface;
use App\Services\Payment\ClickPesa\ClickPesaClient;
use InvalidArgumentException;

class MobileMoneyGatewayManager
{
    public function driver(?string $provider = null): MobileMoneyGatewayInterface
    {
        $provider = $provider ?? config('hotspot.payment.default_provider');

        return match ($provider) {
            'simulated' => app(SimulatedMobileMoneyGateway::class),
            'clickpesa' => $this->resolveClickPesaDriver(),
            'mpesa' => app(MpesaMobileMoneyGateway::class),
            'airtel' => app(AirtelMobileMoneyGateway::class),
            'halopesa' => app(HaloPesaMobileMoneyGateway::class),
            'mixx' => app(MixxMobileMoneyGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$provider}]."),
        };
    }

    /**
     * @return array<string, string>
     */
    public function availableProviders(): array
    {
        $providers = config('hotspot.providers', []);
        $default = config('hotspot.payment.default_provider');

        return collect($providers)
            ->mapWithKeys(fn (array $config, string $key): array => [$key => $config['label'] ?? $key])
            ->when(
                $default === 'simulated',
                fn ($collection) => $collection->only(['simulated'])
            )
            ->when(
                $default === 'clickpesa',
                fn ($collection) => $collection->only(['clickpesa'])
            )
            ->all();
    }

    protected function resolveClickPesaDriver(): MobileMoneyGatewayInterface
    {
        $client = app(ClickPesaClient::class);

        if (! $client->isConfigured()) {
            if ($client->isSandbox() && app()->environment('local')) {
                return app(SimulatedMobileMoneyGateway::class);
            }

            throw new InvalidArgumentException(
                'ClickPesa haijasanidiwa. Weka CLICKPESA_CLIENT_ID na CLICKPESA_API_KEY kwenye .env.'
            );
        }

        return app(ClickPesaMobileMoneyGateway::class);
    }
}
