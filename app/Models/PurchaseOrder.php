<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['supplier_id', 'created_by', 'purchase_number', 'status', 'total', 'notes', 'ordered_at', 'received_at'])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isPending(): bool
    {
        return $this->status === PurchaseOrderStatus::Pending->value;
    }

    public function isReceived(): bool
    {
        return $this->status === PurchaseOrderStatus::Received->value;
    }

    public function isCancelled(): bool
    {
        return $this->status === PurchaseOrderStatus::Cancelled->value;
    }

    public function canReceive(): bool
    {
        return $this->isPending();
    }

    public function canCancel(): bool
    {
        return $this->isPending();
    }

    public function canEdit(): bool
    {
        return $this->isPending();
    }

    public function canDelete(): bool
    {
        return $this->isPending() || $this->isCancelled();
    }

    public function getItemsCountAttribute(): float
    {
        return (float) $this->items->sum('quantity');
    }
}
