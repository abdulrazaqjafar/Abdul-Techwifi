<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table): void {
            $table->boolean('monthly_offer_enabled')->default(true)->after('timezone');
            $table->unsignedTinyInteger('monthly_offer_discount_percent')->default(10)->after('monthly_offer_enabled');
            $table->text('monthly_offer_message')->nullable()->after('monthly_offer_discount_percent');
            $table->string('sms_provider')->default('log')->after('monthly_offer_message');
            $table->string('sms_sender_id')->nullable()->after('sms_provider');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'monthly_offer_enabled',
                'monthly_offer_discount_percent',
                'monthly_offer_message',
                'sms_provider',
                'sms_sender_id',
            ]);
        });
    }
};
