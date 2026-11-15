<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'payment_method_id', 'amount', 'refunded_amount', 'reference', 'status', 'paid_at'])]
class Payment extends Model
{
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function proccessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
