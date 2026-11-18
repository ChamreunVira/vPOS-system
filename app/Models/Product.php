<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'unit_id', 'name', 'sku', 'barcode', 'cost', 'price', 'stock', 'min_stock', 'image', 'status'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'stock' => 'decimal:4',
            'min_stock' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Determine the stock status level.
     */
    public function getStockLevelAttribute(): string
    {
        if ((float) $this->stock <= 0) {
            return 'out_of_stock';
        }

        if ((float) $this->stock <= (float) $this->min_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Scope to products that are low in stock (stock <= min_stock and stock > 0).
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
    }

    /**
     * Scope to products that are completely out of stock.
     */
    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('stock', '<=', 0);
    }

    /**
     * Scope to products with adequate stock.
     */
    public function scopeInStock(Builder $query): void
    {
        $query->whereColumn('stock', '>', 'min_stock');
    }
}
