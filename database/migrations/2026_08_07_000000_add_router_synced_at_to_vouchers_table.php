<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vouchers are now written to the router as soon as they are created, so a
     * customer can log in even when the billing server is offline. This column
     * records whether that write succeeded, so vouchers created while the
     * router was unreachable can be pushed later.
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->timestamp('router_synced_at')->nullable()->after('mikrotik_username');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropColumn('router_synced_at');
        });
    }
};
