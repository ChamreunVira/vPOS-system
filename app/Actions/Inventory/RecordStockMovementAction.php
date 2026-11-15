<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordStockMovementAction
{
    /**
     * Record a stock movement and update product stock atomically within a database transaction.
     *
     * @param  float|int|numeric-string  $quantity  Signed quantity: positive adds stock, negative removes stock
     *
     * @throws ValidationException
     */
    public function execute(
        Product $product,
        StockMovementType|string $type,
        float|int|string $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $note = null,
        ?PurchaseOrder $purchaseOrder = null,
        ?OrderItem $orderItem = null,
        bool $allowNegative = false,
    ): StockMovement {
        $typeString = $type instanceof StockMovementType ? $type->value : (string) $type;
        $numericQuantity = (float) $quantity;

        return DB::transaction(function () use (
            $product,
            $typeString,
            $numericQuantity,
            $user,
            $reference,
            $note,
            $purchaseOrder,
            $orderItem,
            $allowNegative,
        ): StockMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = round((float) $lockedProduct->stock + $numericQuantity, 4);

            if (! $allowNegative && $newBalance < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ["Cannot reduce stock below 0 for {$lockedProduct->name}. Available stock: {$lockedProduct->stock}."],
                ]);
            }

            $lockedProduct->update([
                'stock' => $newBalance,
            ]);

            return StockMovement::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $user?->id ?? auth()->id(),
                'purchase_order_id' => $purchaseOrder?->id,
                'order_item_id' => $orderItem?->id,
                'type' => $typeString,
                'quantity' => $numericQuantity,
                'balance' => $newBalance,
                'reference' => $reference,
                'note' => $note,
            ]);
        });
    }
}
