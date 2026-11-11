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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('status');
            $table->decimal('subtotal');
            $table->decimal('discount_percent');
            $table->decimal('discount_amount');
            $table->decimal('tax_rate');
            $table->decimal('tax_amount');
            $table->decimal('total');
            $table->timestamp('place_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
