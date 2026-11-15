<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\RefundOrderAction;
use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    /**
     * Display a listing of orders with filters and pagination.
     */
    public function index(Request $request): View
    {
        $search = trim($request->input('q', ''));
        $status = $request->input('status');
        $method = $request->input('method');
        $date = $request->input('date');

        $query = Order::query()
            ->with(['customer', 'cashier', 'payments.paymentMethod', 'items.product'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('order_number', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($method && $method !== 'all', function (Builder $query) use ($method): void {
                $query->whereHas('payments.paymentMethod', fn (Builder $q) => $q->where('code', $method));
            })
            ->when($date, function (Builder $query) use ($date): void {
                $query->whereDate('place_at', $date);
            })
            ->latest('id');

        $orders = $query->paginate(15)->withQueryString();

        $paymentMethods = PaymentMethod::query()
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();

        return view('orders.index', [
            'orders' => $orders,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Display the specified order details and receipt preview.
     */
    public function show(Order $order): View
    {
        $order->load(['customer', 'cashier', 'items.product.unit', 'items.unit', 'payments.paymentMethod']);

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Process full or partial refund for the order.
     */
    public function refund(Request $request, Order $order, RefundOrderAction $refundOrder): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.restock' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_method' => ['nullable', 'string', 'max:50'],
        ]);

        $refundOrder->execute(
            order: $order,
            items: $validated['items'],
            reason: $validated['reason'],
            refundMethod: $validated['refund_method'] ?? null,
            user: $request->user(),
        );

        return redirect()->route('orders.show', $order)
            ->with('success', 'Refund processed successfully.');
    }

    /**
     * Cancel a pending order before payment completes.
     */
    public function cancel(Order $order, CancelOrderAction $cancelOrder): RedirectResponse
    {
        $cancelOrder->execute($order);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order cancelled successfully.');
    }

    /**
     * Printable view of order receipt.
     */
    public function receipt(Order $order): View
    {
        $order->load(['customer', 'cashier', 'items.product', 'payments.paymentMethod']);

        return view('orders.receipt', [
            'order' => $order,
        ]);
    }

    /**
     * Export filtered orders to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = trim($request->input('q', ''));
        $status = $request->input('status');
        $method = $request->input('method');
        $date = $request->input('date');

        $query = Order::query()
            ->with(['customer', 'cashier', 'payments.paymentMethod'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('order_number', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($method && $method !== 'all', function (Builder $query) use ($method): void {
                $query->whereHas('payments.paymentMethod', fn (Builder $q) => $q->where('code', $method));
            })
            ->when($date, function (Builder $query) use ($date): void {
                $query->whereDate('place_at', $date);
            })
            ->latest('id');

        $filename = 'orders-export-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order Number', 'Date', 'Customer', 'Cashier', 'Payment Method', 'Payment Status', 'Order Status', 'Subtotal', 'Discount', 'Tax', 'Total']);

            $query->chunk(100, function ($chunk) use ($handle): void {
                /** @var Order $order */
                foreach ($chunk as $order) {
                    fputcsv($handle, [
                        $order->order_number,
                        $order->placed_at->format('Y-m-d H:i'),
                        $order->customer_name,
                        $order->cashier_name,
                        strtoupper($order->payment_method),
                        ucfirst(str_replace('_', ' ', $order->payment_status)),
                        ucfirst(str_replace('_', ' ', $order->status)),
                        $order->subtotal,
                        $order->discount_amount,
                        $order->tax_amount,
                        $order->total,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
