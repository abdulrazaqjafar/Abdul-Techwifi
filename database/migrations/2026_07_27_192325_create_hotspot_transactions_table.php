<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->string('provider')->default('mpesa');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'expired'])->default('pending');
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mac_address')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('link_login')->nullable();
            $table->text('link_orig')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reference']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_transactions');
    }
};
