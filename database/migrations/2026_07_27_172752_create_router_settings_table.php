<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('router_settings', function (Blueprint $table) {
            $table->id();
            $table->string('router_ip');
            $table->unsignedInteger('router_port')->default(8728);
            $table->string('router_username');
            $table->text('router_password');
            $table->string('hotspot_interface')->default('hotspot');
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_settings');
    }
};
