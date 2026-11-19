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
        if (Schema::hasTable('cash_sesions') && ! Schema::hasTable('cash_sessions')) {
            Schema::rename('cash_sesions', 'cash_sessions');
        }

        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->foreignId('cash_session_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('cash_movements', function (Blueprint $table): void {
                $table->foreignId('cash_session_id')->nullable(false)->change();
            });
        } catch (Throwable) {
            // Ignore
        }

        if (Schema::hasTable('cash_sessions') && ! Schema::hasTable('cash_sesions')) {
            Schema::rename('cash_sessions', 'cash_sesions');
        }
    }
};
