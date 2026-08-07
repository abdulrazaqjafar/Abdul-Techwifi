<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Package::query()->where('name', 'Tarangire Lite')->update(['is_active' => false]);

        $packages = [
            [
                'name' => 'Serengeti Daily',
                'duration_hours' => 0,
                'duration_days' => 1,
                'price' => 1000,
                'profile_name' => 'serengeti-daily',
                'description' => '1 day of high-speed WiFi access',
                'is_active' => true,
            ],
            [
                'name' => 'Ruaha Weekly',
                'duration_hours' => 0,
                'duration_days' => 7,
                'price' => 7000,
                'profile_name' => 'ruaha-weekly',
                'description' => '7 days of high-speed WiFi access',
                'is_active' => true,
            ],
            [
                'name' => 'Ngorongoro Fortnight',
                'duration_hours' => 0,
                'duration_days' => 14,
                'price' => 14000,
                'profile_name' => 'ngorongoro-fortnight',
                'description' => '2 weeks of high-speed WiFi access',
                'is_active' => true,
            ],
            [
                'name' => 'Kilimanjaro Unlimited',
                'duration_hours' => 0,
                'duration_days' => 30,
                'price' => 25000,
                'profile_name' => 'kilimanjaro-unlimited',
                'description' => '30 days of premium WiFi access',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $package) {
            Package::query()->updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
