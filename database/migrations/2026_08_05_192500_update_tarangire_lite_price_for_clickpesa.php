<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')
            ->where('name', 'Tarangire Lite')
            ->where('price', '<', 908)
            ->update(['price' => 1000]);
    }

    public function down(): void
    {
        DB::table('packages')
            ->where('name', 'Tarangire Lite')
            ->where('price', 1000)
            ->update(['price' => 500]);
    }
};
