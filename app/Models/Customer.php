<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'phone', 'email', 'address', 'notes', 'status'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope to active customers.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Get the total amount spent across all completed orders.
     */
    public function getTotalSpentAttribute(): float
    {
        return (float) ($this->attributes['total_spent'] ?? $this->orders()->sum('total'));
    }

    /**
     * Get a human-readable date of the customer's last order or creation.
     */
    public function getLastVisitAttribute(): string
    {
        $lastOrderDate = $this->orders()->latest('place_at')->value('place_at');

        if ($lastOrderDate) {
            return Carbon::parse($lastOrderDate)->format('M j, Y');
        }

        return $this->created_at ? $this->created_at->format('M j, Y') : '—';
    }
}
