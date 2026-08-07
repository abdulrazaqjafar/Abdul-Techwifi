<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->string('device_type')->nullable()->after('activation_ip');
            $table->string('device_os')->nullable()->after('device_type');
            $table->string('device_browser')->nullable()->after('device_os');
            $table->string('device_hostname')->nullable()->after('device_browser');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn(['device_type', 'device_os', 'device_browser', 'device_hostname']);
        });
    }
};
