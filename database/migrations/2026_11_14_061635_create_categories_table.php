<?php

use App\Enums\SoftDeleteStatus;
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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->enum('status', SoftDeleteStatus::all())->default(SoftDeleteStatus::Active->value);
            $table->timestamps();
        });

        $productForeignKeys = Schema::getForeignKeys('products');
        $hasCategoryForeignKey = collect($productForeignKeys)->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['category_id'] && $foreignKey['foreign_table'] === 'categories',
        );
        $hasUnitForeignKey = collect($productForeignKeys)->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['unit_id'] && $foreignKey['foreign_table'] === 'units',
        );

        Schema::table('products', function (Blueprint $table) use ($hasCategoryForeignKey, $hasUnitForeignKey): void {
            if (! $hasCategoryForeignKey) {
                $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            }

            if (! $hasUnitForeignKey) {
                $table->foreign('unit_id')->references('id')->on('units')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
        });

        Schema::dropIfExists('categories');
    }
};
