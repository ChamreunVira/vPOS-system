<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\RecordStockMovementAction;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /**
     * Display a listing of stock movements.
     */
    public function index(Request $request): View|RedirectResponse
    {
        return redirect()->route('inventory.index', ['tab' => 'movements']);
    }

    /**
     * Store a newly created stock movement.
     */
    public function store(Request $request, RecordStockMovementAction $recordStockMovement): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string'],
            'quantity' => ['required', 'numeric'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        $recordStockMovement->execute(
            product: $product,
            type: $data['type'],
            quantity: $data['quantity'],
            user: $request->user(),
            reference: $data['reference'] ?? null,
            note: $data['note'] ?? null,
            purchaseOrder: isset($data['purchase_order_id']) ? PurchaseOrder::find($data['purchase_order_id']) : null,
            orderItem: isset($data['order_item_id']) ? OrderItem::find($data['order_item_id']) : null,
        );

        return redirect()->route('inventory.index', ['tab' => 'movements'])
            ->with('success', 'Stock movement recorded successfully.');
    }
}
