<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_snapshots', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('mikrotik_username');
            $table->string('mac_address')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('customer_service_snapshots', function (Blueprint $table): void {
            $table->dropColumn(['ip_address', 'mac_address']);
        });
    }
};
