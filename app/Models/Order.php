<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_number', 'customer_id', 'cashier_id', 'status', 'subtotal', 'discount_percent', 'discount_amount', 'tax_rate', 'tax_amount', 'total', 'place_at'])]
class Order extends Model
{
}
