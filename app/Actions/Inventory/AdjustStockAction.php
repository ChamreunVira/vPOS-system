<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdjustStockAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordStockMovement,
    ) {}

    /**
     * Process a manual stock adjustment or count correction.
     *
     * @param  string  $type  'stock_in', 'stock_out', 'adjustment', or a StockMovementType value
     * @param  float|int|numeric-string  $quantity
     * @param  string  $adjustmentMode  'delta' or 'count'
     *
     * @throws ValidationException
     */
    public function execute(
        Product $product,
        string $type,
        float|int|string $quantity,
        ?string $reason = null,
        ?string $note = null,
        string $adjustmentMode = 'count',
        ?User $user = null,
        ?string $reference = null,
    ): StockMovement {
        $rawQuantity = (float) $quantity;
        $reasonClean = trim($reason ?? '');
        $noteClean = trim($note ?? '');
        $currentStock = (float) $product->stock;

        $signedQuantity = 0.0;
        $movementType = StockMovementType::Adjustment->value;

        if ($type === 'stock_in' || $type === StockMovementType::In->value) {
            $signedQuantity = abs($rawQuantity);
            $movementType = match (strtolower($reasonClean)) {
                'supplier delivery' => StockMovementType::Purchase->value,
                'customer return' => StockMovementType::Return->value,
                default => StockMovementType::Adjustment->value,
            };
        } elseif ($type === 'stock_out' || $type === StockMovementType::Out->value) {
            $signedQuantity = -abs($rawQuantity);
            $movementType = match (strtolower($reasonClean)) {
                'damaged', 'expired' => StockMovementType::Damage->value,
                default => StockMovementType::Adjustment->value,
            };
        } elseif ($type === 'adjustment' || $type === StockMovementType::Adjustment->value) {
            if ($adjustmentMode === 'count') {
                $signedQuantity = round($rawQuantity - $currentStock, 4);
            } else {
                $signedQuantity = $rawQuantity;
            }

            if ($signedQuantity < 0 && in_array(strtolower($reasonClean), ['damaged', 'expired'], true)) {
                $movementType = StockMovementType::Damage->value;
            } else {
                $movementType = StockMovementType::Adjustment->value;
            }
        } else {
            $signedQuantity = $rawQuantity;
            $movementType = $type;
        }

        if ($signedQuantity == 0.0) {
            throw ValidationException::withMessages([
                'quantity' => ['The entered count matches the current stock level. No adjustment needed.'],
            ]);
        }

        if ($reference === null || trim($reference) === '') {
            $reference = 'ADJ-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));
        }

        $reasonLabel = $reasonClean !== '' ? $reasonClean : 'Manual adjustment';
        $fullNote = $noteClean !== '' ? ($reasonLabel.': '.$noteClean) : $reasonLabel;

        return $this->recordStockMovement->execute(
            product: $product,
            type: $movementType,
            quantity: $signedQuantity,
            user: $user,
            reference: $reference,
            note: $fullNote,
        );
    }
}
