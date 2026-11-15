<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['order_number', 'customer_id', 'cashier_id', 'status', 'subtotal', 'discount_percent', 'discount_amount', 'tax_rate', 'tax_amount', 'total', 'place_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'place_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getNumberAttribute(): string
    {
        return $this->order_number;
    }

    public function getPlacedAtAttribute(): Carbon
    {
        return $this->place_at ?? $this->created_at ?? now();
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->getRelationValue('customer')?->name ?? 'Walk-in customer';
    }

    public function getMethodAttribute(): string
    {
        return $this->payment_method;
    }

    public function getPaymentMethodAttribute(): string
    {
        return $this->payments->first()?->paymentMethod?->code ?? 'cash';
    }

    public function getPaymentStatusAttribute(): string
    {
        $paid = (float) $this->payments->where('status', 'paid')->sum('amount');
        $refunded = (float) $this->payments->sum('refunded_amount');

        if ($refunded >= (float) $this->total && (float) $this->total > 0) {
            return 'refunded';
        }

        if ($refunded > 0) {
            return 'partially_refunded';
        }

        if ($paid >= (float) $this->total && (float) $this->total > 0) {
            return 'paid';
        }

        return 'unpaid';
    }

    public function isRefundable(): bool
    {
        return in_array($this->status, ['completed', 'partially_refunded'], true);
    }

    public function isCancellable(): bool
    {
        return $this->status === 'pending';
    }
}
