<?php

namespace App\Actions\Orders;

use App\Helper\CartCalculator;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PaymentStrategyResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePosOrderAction
{
    public function __construct(
        private PaymentStrategyResolver $paymentResolver
    ) {}

    public function execute(array $data, User $cashier): array
    {
        $cashierId = $cashier->getAuthIdentifier();

        return DB::transaction(function () use ($data, $cashierId): array {

            $productIds = collect($data['items'])
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $products = Product::query()
                ->with('unit')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Ensure all requested products exist.
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products do not exist.',
                ]);
            }

            $customer = null;

            if (! empty($data['customer_id'])) {
                $customer = Customer::query()
                    ->whereKey($data['customer_id'])
                    ->where('status', 'active')
                    ->first();

                if (! $customer) {
                    throw ValidationException::withMessages([
                        'customer_id' => 'Choose an active customer or use walk-in customer.',
                    ]);
                }
            }

            $discountPercent = (float) $data['discount_percent'];

            $vatEnabled = (bool) Setting::get(
                'currency.vat_enabled',
                config('vpos.vat_rate', 0) > 0
            );

            $taxRate = $vatEnabled ? (float) Setting::get('currency.vat_rate', config('vpos.vat_rate', 0)) : 0.0;

            $cart = CartCalculator::process(
                $data['items'],
                $products,
                $discountPercent,
                $taxRate
            );

            $lineItems = $cart['line_items'];
            $subtotal = $cart['subtotal'];
            $discountAmount = $cart['discount_amount'];
            $taxAmount = $cart['tax_amount'];
            $total = $cart['total'];

            $paymentCode = $data['payment_method'];

            $paymentMethod = PaymentMethod::query()
                ->where('code', $paymentCode)
                ->where('is_enable', true)
                ->first();

            if (! $paymentMethod) {
                throw ValidationException::withMessages([
                    'payment_method' => 'This payment method is unavailable.',
                ]);
            }

            $strategy = $this->paymentResolver->resolver($paymentCode);

            $paymentResult = $strategy->process($total, $data);

            $received = $paymentResult['received'];
            $change = $paymentResult['change'];

            $now = now();

            $order = Order::create([
                'order_number' => 'pending-'.Str::ulid(),
                'customer_id' => $customer?->id,
                'cashier_id' => $cashierId,

                'status' => $paymentResult['status'] === 'paid'
                    ? 'completed'
                    : 'pending',

                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'place_at' => $now,
            ]);

            // Generate invoice number using order ID.
            $orderNumber = 'INV-'.str_pad(
                (string) $order->id,
                6,
                '0',
                STR_PAD_LEFT
            );

            $order->update([
                'order_number' => $orderNumber,
            ]);

            $receiptLines = [];
            $stockBalances = [];

            foreach ($lineItems as $lineItem) {

                /** @var Product $product */
                $product = $lineItem['product'];

                $quantity = $lineItem['quantity'];

                $balance = round(
                    (float) $product->stock - $quantity,
                    2
                );

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_id' => $product->unit_id,
                    'conversion_factor' => '1.0000',
                    'unit_price' => $lineItem['price'],
                    'unit_cost' => $lineItem['cost'],
                    'line_total' => $lineItem['line_total'],
                ]);

                $product->update([
                    'stock' => $balance,
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $cashierId,
                    'purchase_order_id' => null,
                    'order_item_id' => $orderItem->id,
                    'type' => 'sale',
                    'quantity' => -$quantity,
                    'balance' => $balance,
                    'reference' => $order->id,
                    'note' => 'Point of sale '.$orderNumber,
                ]);

                $receiptLines[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $lineItem['price'],
                    'lineTotal' => $lineItem['line_total'],
                ];

                $stockBalances[$product->id] = $balance;
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $paymentResult['amount'],
                'refunded_amount' => 0,

                'reference' => $paymentResult['reference']
                    ?? 'PAY-'.$order->id,

                'status' => $paymentResult['status'],
                'paid_at' => $paymentResult['paid_at'],
            ]);

            return [
                'receipt' => [
                    'number' => $orderNumber,
                    'placedAt' => $now->format('d M Y, H:i'),
                    'customer' => $customer?->name ?? 'Walk-in customer',

                    'lines' => $receiptLines,

                    'subtotal' => $subtotal,
                    'discountPercent' => $discountPercent,
                    'discountAmount' => $discountAmount,
                    'vatAmount' => $taxAmount,
                    'total' => $total,

                    'method' => $paymentCode,
                    'received' => $received,
                    'change' => $change,

                    'paymentStatus' => $paymentResult['status'],
                    'orderStatus' => $order->status,
                ],

                'stock' => $stockBalances,
            ];
        }, 3);
    }
}
