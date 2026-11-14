<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?? '',
                'barcode' => $product->barcode ?? '',
                'category' => $product->category?->name ?? 'Uncategorized',
                'price' => (float) $product->price,
                'stock' => (float) $product->stock,
                'min_stock' => (float) $product->min_stock,
                'status' => $product->status,
                'emoji' => '🛒',
            ])
            ->values();

        $customers = Customer::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pos.index', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $paymentMethods = config('vpos.payment_methods', ['cash']);
        $discountOptions = config('vpos.discount_options', [0]);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'discount_percent' => ['required', 'numeric', Rule::in($discountOptions)],
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'amount_received' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
        ]);

        $receipt = DB::transaction(function () use ($data): array {
            $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id): int => (int) $id)->sort()->values();
            $products = Product::query()
                ->with('unit')
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

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

            $lineItems = [];
            $subtotal = 0.0;

            foreach ($data['items'] as $item) {
                /** @var Product|null $product */
                $product = $products->get((int) $item['product_id']);
                $quantity = (float) $item['quantity'];

                if (! $product || $product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'items' => 'A product in this sale is no longer available.',
                    ]);
                }

                if (round($quantity, 2) !== $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Product quantities can have at most two decimal places.',
                    ]);
                }

                if (! $product->unit?->allow_decimal && floor($quantity) !== $quantity) {
                    throw ValidationException::withMessages([
                        'items' => $product->name.' must be sold in whole units.',
                    ]);
                }

                if ($quantity > (float) $product->stock) {
                    throw ValidationException::withMessages([
                        'items' => 'There is not enough stock for '.$product->name.'. Refresh the sale and try again.',
                    ]);
                }

                $lineTotal = round((float) $product->price * $quantity, 2);
                $subtotal = round($subtotal + $lineTotal, 2);
                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => (float) $product->price,
                    'cost' => (float) $product->cost,
                    'line_total' => $lineTotal,
                ];
            }

            $discountPercent = (float) $data['discount_percent'];
            $discountAmount = round($subtotal * $discountPercent / 100, 2);
            $taxRate = (float) config('vpos.vat_rate', 0);
            $taxAmount = round(($subtotal - $discountAmount) * $taxRate / 100, 2);
            $total = round($subtotal - $discountAmount + $taxAmount, 2);
            $paymentCode = $data['payment_method'];
            $paymentLabels = ['cash' => 'Cash', 'khqr' => 'KHQR', 'card' => 'Card'];
            $paymentMethod = PaymentMethod::query()->firstOrCreate(
                ['code' => $paymentCode],
                ['name' => $paymentLabels[$paymentCode], 'is_enable' => true],
            );

            if (! $paymentMethod->is_enable) {
                throw ValidationException::withMessages([
                    'payment_method' => 'This payment method is disabled.',
                ]);
            }

            $received = $paymentCode === 'cash' ? round((float) $data['amount_received'], 2) : $total;
            if ($paymentCode === 'cash' && $received < $total) {
                throw ValidationException::withMessages([
                    'amount_received' => 'The amount received must cover the total.',
                ]);
            }
            $change = round(max($received - $total, 0), 2);
            $now = now();

            $order = Order::create([
                'order_number' => 'pending-'.Str::ulid(),
                'customer_id' => $customer?->id,
                'cashier_id' => Auth::id(),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'place_at' => $now,
            ]);
            $orderNumber = 'INV-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
            $order->update(['order_number' => $orderNumber]);

            $receiptLines = [];
            $stockBalances = [];

            foreach ($lineItems as $lineItem) {
                /** @var Product $product */
                $product = $lineItem['product'];
                $quantity = $lineItem['quantity'];
                $balance = round((float) $product->stock - $quantity, 2);

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

                $product->update(['stock' => $balance]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
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
                'amount' => $total,
                'refunded_amount' => 0,
                'reference' => 'PAY-'.$order->id,
                'status' => 'paid',
                'paid_at' => $now,
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
                ],
                'stock' => $stockBalances,
            ];
        }, 3);

        return response()->json($receipt);
    }
}
