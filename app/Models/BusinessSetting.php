<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessSettingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'business_name',
        'tagline',
        'business_logo',
        'phone',
        'whatsapp',
        'address',
        'currency',
        'timezone',
        'monthly_offer_enabled',
        'monthly_offer_discount_percent',
        'monthly_offer_message',
        'sms_provider',
        'sms_sender_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_offer_enabled' => 'boolean',
        ];
    }

    public function defaultOfferMessage(): string
    {
        return 'Habari {name}! {business} inakupa punguzo la {discount}% kwa Wi-Fi. Tumia code {code} kabla ya {expires}. Asante!';
    }

    public static function getSettings(): self
    {
        return static::query()->firstOrCreate([], [
            'business_name' => config('app.name', 'WiFi Hotspot'),
            'currency' => 'USD',
            'timezone' => config('app.timezone', 'UTC'),
        ]);
    }
}
