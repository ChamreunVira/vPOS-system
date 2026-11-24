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
        Schema::create('pos_scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pairing_token_hash', 64)->nullable()->index();
            $table->string('device_token_hash', 64)->nullable()->index();
            $table->string('pairing_code_hash', 64)->nullable()->index();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('pos_scan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_scan_session_id')->constrained('pos_scan_sessions')->cascadeOnDelete();
            $table->string('barcode', 100);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['pos_scan_session_id', 'id']);
            $table->index(['pos_scan_session_id', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_scan_events');
        Schema::dropIfExists('pos_scan_sessions');
    }
};
