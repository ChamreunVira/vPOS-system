<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'product_id', 'quantity', 'unit_id', 'conversion_factor', 'unit_price', 'unit_cost', 'line_total'])]
class OrderItem extends Model
{
}
