<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_service_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mikrotik_username')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('rate_limit')->nullable();
            $table->unsignedBigInteger('bytes_in')->default(0);
            $table->unsignedBigInteger('bytes_out')->default(0);
            $table->unsignedInteger('uptime_seconds')->default(0);
            $table->string('quality_score')->default('unknown');
            $table->string('quality_label')->default('Haijulikani');
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['customer_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_snapshots');
    }
};
