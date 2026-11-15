<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'user_id', 'purchase_order_id', 'order_item_id', 'type', 'quantity', 'balance', 'reference', 'note'])]
class StockMovement extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
