<?php

namespace App\Actions\Purchases;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

class CancelPurchaseOrderAction
{
    /**
     * Cancel a pending purchase order.
     *
     * @throws ValidationException
     */
    public function execute(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if (! $purchaseOrder->canCancel()) {
            throw ValidationException::withMessages([
                'status' => ["Purchase order {$purchaseOrder->purchase_number} cannot be cancelled because its status is {$purchaseOrder->status}."],
            ]);
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::Cancelled->value,
        ]);

        return $purchaseOrder->fresh();
    }
}
