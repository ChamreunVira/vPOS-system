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
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->change();
            $table->unique('order_number');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_order_number_unique');
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
