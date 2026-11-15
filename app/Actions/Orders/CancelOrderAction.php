<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class CancelOrderAction
{
    /**
     * Cancel a pending order before payment completes.
     *
     *
     * @throws ValidationException
     */
    public function execute(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending->value) {
            throw ValidationException::withMessages([
                'order' => ['Only pending orders can be cancelled. Completed orders must be refunded.'],
            ]);
        }

        $order->update([
            'status' => OrderStatus::Cancelled->value,
        ]);

        return $order->fresh();
    }
}
