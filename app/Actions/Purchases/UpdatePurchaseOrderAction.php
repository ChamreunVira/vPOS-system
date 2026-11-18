<?php

namespace App\Actions\Purchases;

use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePurchaseOrderAction
{
    /**
     * @param array{
     *     supplier_id: int,
     *     ordered_at?: string|Carbon|null,
     *     notes?: string|null,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: float|int|numeric-string,
     *         unit_cost: float|int|numeric-string,
     *         unit_id?: int|null
     *     }>
     * } $data
     *
     * @throws ValidationException
     */
    public function execute(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        if (! $purchaseOrder->canEdit()) {
            throw ValidationException::withMessages([
                'status' => ["Purchase order {$purchaseOrder->purchase_number} cannot be edited because its status is {$purchaseOrder->status}."],
            ]);
        }

        if (empty($data['items'])) {
            throw ValidationException::withMessages([
                'items' => ['At least one item is required in the purchase order.'],
            ]);
        }

        return DB::transaction(function () use ($purchaseOrder, $data): PurchaseOrder {
            $total = 0.0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $quantity = max((float) ($item['quantity'] ?? 0), 0);
                $unitCost = max((float) ($item['unit_cost'] ?? 0), 0);
                $lineTotal = round($quantity * $unitCost, 2);
                $total += $lineTotal;

                $itemsData[] = [
                    'product_id' => (int) $item['product_id'],
                    'unit_id' => ! empty($item['unit_id']) ? (int) $item['unit_id'] : null,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'received_quantity' => 0,
                ];
            }

            $orderedAt = ! empty($data['ordered_at']) ? Carbon::parse($data['ordered_at']) : $purchaseOrder->ordered_at;

            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'total' => round($total, 2),
                'notes' => $data['notes'] ?? null,
                'ordered_at' => $orderedAt,
            ]);

            // Replace items
            $purchaseOrder->items()->delete();
            foreach ($itemsData as $itemData) {
                $purchaseOrder->items()->create($itemData);
            }

            return $purchaseOrder->fresh(['items.product', 'supplier', 'creator']);
        });
    }
}
