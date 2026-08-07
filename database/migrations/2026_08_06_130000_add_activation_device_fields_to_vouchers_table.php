<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->string('activation_mac')->nullable()->after('activated_at');
            $table->string('activation_ip', 45)->nullable()->after('activation_mac');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn(['activation_mac', 'activation_ip']);
        });
    }
};
