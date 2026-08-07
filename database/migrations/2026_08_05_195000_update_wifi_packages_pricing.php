<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')->where('name', 'Tarangire Lite')->update(['is_active' => false]);

        DB::table('packages')->where('name', 'Serengeti Daily')->update([
            'duration_hours' => 0,
            'duration_days' => 1,
            'price' => 1000,
            'description' => '1 day of high-speed WiFi access',
        ]);

        DB::table('packages')->where('name', 'Ruaha Weekly')->update([
            'duration_hours' => 0,
            'duration_days' => 7,
            'price' => 7000,
            'description' => '7 days of high-speed WiFi access',
        ]);

        DB::table('packages')->updateOrInsert(
            ['name' => 'Ngorongoro Fortnight'],
            [
                'duration_hours' => 0,
                'duration_days' => 14,
                'price' => 14000,
                'profile_name' => 'ngorongoro-fortnight',
                'description' => '2 weeks of high-speed WiFi access',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('packages')->where('name', 'Kilimanjaro Unlimited')->update([
            'duration_hours' => 0,
            'duration_days' => 30,
            'price' => 25000,
            'description' => '30 days of premium WiFi access',
        ]);
    }

    public function down(): void
    {
        DB::table('packages')->where('name', 'Tarangire Lite')->update(['is_active' => true, 'price' => 1000]);

        DB::table('packages')->where('name', 'Serengeti Daily')->update([
            'duration_hours' => 24,
            'duration_days' => 0,
            'price' => 1000,
        ]);

        DB::table('packages')->where('name', 'Ruaha Weekly')->update(['price' => 6000]);

        DB::table('packages')->where('name', 'Ngorongoro Fortnight')->delete();

        DB::table('packages')->where('name', 'Kilimanjaro Unlimited')->update(['price' => 25000]);
    }
};
