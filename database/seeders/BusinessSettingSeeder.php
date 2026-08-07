<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = BusinessSetting::query()->first();

        $data = [
            'tagline' => 'Connect fast, browse securely, and enjoy affordable internet packages.',
            'business_logo' => null,
            'phone' => '+255679014566',
            'whatsapp' => '+255679014566',
            'address' => 'Dar es Salaam, Tanzania',
            'currency' => 'TSh',
            'timezone' => 'Africa/Dar_es_Salaam',
        ];

        if ($settings) {
            $settings->update($data);

            return;
        }

        BusinessSetting::query()->create(array_merge([
            'business_name' => 'TechSphere WiFi',
        ], $data));
    }
}
