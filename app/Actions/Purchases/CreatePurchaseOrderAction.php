<?php

namespace App\Actions\Purchases;

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrderAction
{
    public function __construct(
        private readonly ReceivePurchaseOrderAction $receivePurchaseOrder,
    ) {}

    /**
     * @param array{
     *     supplier_id: int,
     *     ordered_at?: string|Carbon|null,
     *     notes?: string|null,
     *     status?: string|null,
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
    public function execute(array $data, ?User $user = null): PurchaseOrder
    {
        if (empty($data['items'])) {
            throw ValidationException::withMessages([
                'items' => ['At least one item is required to create a purchase order.'],
            ]);
        }

        $userId = $user?->id ?? auth()->id();
        $targetStatus = $data['status'] ?? PurchaseOrderStatus::Pending->value;
        $orderedAt = ! empty($data['ordered_at']) ? Carbon::parse($data['ordered_at']) : now();

        return DB::transaction(function () use ($data, $userId, $targetStatus, $orderedAt, $user): PurchaseOrder {
            // Calculate total from items
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

            // Create initial purchase order with placeholder purchase_number
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'created_by' => $userId,
                'purchase_number' => 'PO-temp',
                'status' => PurchaseOrderStatus::Pending->value,
                'total' => round($total, 2),
                'notes' => $data['notes'] ?? null,
                'ordered_at' => $orderedAt,
                'received_at' => null,
            ]);

            $purchaseNumber = 'PO-'.str_pad((string) $purchaseOrder->id, 6, '0', STR_PAD_LEFT);
            $purchaseOrder->update(['purchase_number' => $purchaseNumber]);

            // Save items
            foreach ($itemsData as $itemData) {
                $purchaseOrder->items()->create($itemData);
            }

            // If immediately received, run receive action
            if ($targetStatus === PurchaseOrderStatus::Received->value) {
                return $this->receivePurchaseOrder->execute($purchaseOrder, $user);
            }

            return $purchaseOrder->fresh(['items.product', 'supplier', 'creator']);
        });
    }
}
