<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'payment_method_id', 'amount', 'refunded_amount', 'reference', 'status', 'paid_at'])]
class Payment extends Model
{
}
