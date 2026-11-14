<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'user_id', 'purchase_order_id', 'order_item_id', 'type', 'quantity', 'balance', 'reference', 'note'])]
class StockMovement extends Model
{
}
