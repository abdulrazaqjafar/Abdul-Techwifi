<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::query()->where('email', 'admin@techsphere.com')->delete();

        Admin::query()->updateOrCreate(
            ['email' => 'abdulrazaqjafar66@gmail.com'],
            [
                'name' => 'TechSphere Admin',
                'password' => 'JoaoAbdul345+?',
                'role' => 'admin',
            ]
        );

        Admin::query()->updateOrCreate(
            ['email' => 'cashier@techsphere.com'],
            [
                'name' => 'Cashier Demo',
                'password' => 'Cashier123!',
                'role' => 'cashier',
            ]
        );
    }
}
