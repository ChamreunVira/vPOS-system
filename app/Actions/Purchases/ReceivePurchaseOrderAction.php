<?php

namespace App\Actions\Purchases;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchaseOrderAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordStockMovement,
    ) {}

    /**
     * Mark a pending purchase order as received and update product stock levels.
     *
     * @throws ValidationException
     */
    public function execute(PurchaseOrder $purchaseOrder, ?User $user = null): PurchaseOrder
    {
        if (! $purchaseOrder->canReceive()) {
            throw ValidationException::withMessages([
                'status' => ["Purchase order {$purchaseOrder->purchase_number} cannot be received because its status is {$purchaseOrder->status}."],
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $user): PurchaseOrder {
            $purchaseOrder->loadMissing(['items.product', 'supplier']);

            foreach ($purchaseOrder->items as $item) {
                $product = $item->product;

                if (! $product) {
                    continue;
                }

                $quantity = (float) $item->quantity;

                // Record stock movement (adds to stock)
                $this->recordStockMovement->execute(
                    product: $product,
                    type: StockMovementType::Purchase,
                    quantity: $quantity,
                    user: $user,
                    reference: $purchaseOrder->purchase_number,
                    note: 'Purchase received from '.($purchaseOrder->supplier?->name ?? 'Supplier'),
                    purchaseOrder: $purchaseOrder,
                );

                // Update product cost with the latest purchase cost
                $product->update([
                    'cost' => $item->unit_cost,
                ]);

                $item->update([
                    'received_quantity' => $quantity,
                ]);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::Received->value,
                'received_at' => now(),
            ]);

            return $purchaseOrder->fresh(['items.product', 'supplier', 'creator', 'stockMovements']);
        });
    }
}
