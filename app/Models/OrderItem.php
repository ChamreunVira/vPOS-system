<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'product_id', 'quantity', 'unit_id', 'conversion_factor', 'unit_price', 'unit_cost', 'line_total'])]
class OrderItem extends Model
{

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
