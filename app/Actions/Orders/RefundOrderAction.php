<?php

namespace App\Actions\Orders;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundOrderAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordStockMovement,
    ) {}

    /**
     * Process a full or partial refund for an order.
     *
     * @param  array<int, array{order_item_id: int, quantity: numeric, restock?: bool}>  $items
     *
     * @throws ValidationException
     */
    public function execute(
        Order $order,
        array $items,
        string $reason,
        ?string $refundMethod = null,
        ?User $user = null,
    ): Order {
        if (! in_array($order->status, [OrderStatus::Completed->value, OrderStatus::PartiallyRefunded->value], true)) {
            throw ValidationException::withMessages([
                'order' => ['Only completed or partially refunded orders can be refunded.'],
            ]);
        }

        return DB::transaction(function () use ($order, $items, $reason, $user): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->load(['items.product', 'payments']);

            $totalRefundAmount = 0.0;
            $hasReturnedItems = false;
            $allOriginalQuantity = 0.0;
            $allRefundedQuantity = 0.0;

            foreach ($lockedOrder->items as $orderItem) {
                $itemOriginalQty = (float) $orderItem->quantity;
                $allOriginalQuantity += $itemOriginalQty;

                $alreadyRefunded = (float) StockMovement::query()
                    ->where('order_item_id', $orderItem->id)
                    ->where('type', StockMovementType::Return->value)
                    ->sum('quantity');

                $requestedLine = collect($items)->first(function ($line) use ($orderItem): bool {
                    return isset($line['order_item_id']) && (int) $line['order_item_id'] === $orderItem->id;
                });

                $qtyToRefund = $requestedLine ? (float) ($requestedLine['quantity'] ?? 0) : 0.0;
                $restock = $requestedLine ? filter_var($requestedLine['restock'] ?? true, FILTER_VALIDATE_BOOLEAN) : true;

                if ($qtyToRefund > 0) {
                    $remainingQty = max($itemOriginalQty - $alreadyRefunded, 0);

                    if ($qtyToRefund > $remainingQty) {
                        throw ValidationException::withMessages([
                            'items' => ["Cannot refund more than {$remainingQty} remaining for {$orderItem->product->name}."],
                        ]);
                    }

                    // Proportion of the order total for this item (accounting for discounts and tax)
                    $effectiveRate = (float) $lockedOrder->subtotal > 0
                        ? ((float) $lockedOrder->total / (float) $lockedOrder->subtotal)
                        : 1.0;

                    $itemRefundAmount = round($qtyToRefund * (float) $orderItem->unit_price * $effectiveRate, 2);
                    $totalRefundAmount += $itemRefundAmount;
                    $hasReturnedItems = true;

                    if ($restock) {
                        $this->recordStockMovement->execute(
                            product: $orderItem->product,
                            type: StockMovementType::Return,
                            quantity: $qtyToRefund,
                            user: $user,
                            reference: $lockedOrder->order_number,
                            note: "Order refund ({$lockedOrder->order_number}): {$reason}",
                            orderItem: $orderItem,
                        );
                    } else {
                        // Refund without restock (e.g. damaged goods per BR-STK-06)
                        if (in_array(strtolower(trim($reason)), ['damaged', 'damaged item', 'expired', 'expired product'], true)) {
                            StockMovement::create([
                                'product_id' => $orderItem->product_id,
                                'user_id' => $user?->id ?? auth()->id(),
                                'purchase_order_id' => null,
                                'order_item_id' => $orderItem->id,
                                'type' => StockMovementType::Damage->value,
                                'quantity' => 0,
                                'balance' => $orderItem->product->stock,
                                'reference' => $lockedOrder->order_number,
                                'note' => "Refunded damaged goods (no restock): {$reason}",
                            ]);
                        }
                    }

                    $allRefundedQuantity += ($alreadyRefunded + $qtyToRefund);
                } else {
                    $allRefundedQuantity += $alreadyRefunded;
                }
            }

            if (! $hasReturnedItems) {
                throw ValidationException::withMessages([
                    'items' => ['At least one item must have a refund quantity greater than 0.'],
                ]);
            }

            // Allocate refund amount across payments
            $remainingRefundToAllocate = min($totalRefundAmount, (float) $lockedOrder->total);

            foreach ($lockedOrder->payments as $payment) {
                if ($remainingRefundToAllocate <= 0) {
                    break;
                }

                $maxRefundable = max((float) $payment->amount - (float) $payment->refunded_amount, 0);

                if ($maxRefundable <= 0) {
                    continue;
                }

                $allocated = min($remainingRefundToAllocate, $maxRefundable);
                $newRefunded = round((float) $payment->refunded_amount + $allocated, 2);

                $payment->update([
                    'refunded_amount' => $newRefunded,
                    'status' => $newRefunded >= (float) $payment->amount
                        ? PaymentStatus::Refunded->value
                        : PaymentStatus::PartiallyRefunded->value,
                ]);

                $remainingRefundToAllocate -= $allocated;
            }

            // Update order status
            $isFullyRefunded = $allRefundedQuantity >= $allOriginalQuantity
                || $lockedOrder->payments()->sum('refunded_amount') >= (float) $lockedOrder->total;

            $lockedOrder->update([
                'status' => $isFullyRefunded ? OrderStatus::Refunded->value : OrderStatus::PartiallyRefunded->value,
            ]);

            return $lockedOrder->fresh(['items.product', 'payments.paymentMethod', 'customer', 'cashier']);
        });
    }
}
