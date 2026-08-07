<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('offer_code')->unique();
            $table->unsignedTinyInteger('discount_percent');
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->string('month_key', 7);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'month_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_offers');
    }
};
