<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stockMovements = StockMovement::with(['product', 'user', 'purchaseOrder', 'orderItem'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('inventory.index', compact('stockMovements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $$request->validate([
            'product_id' => ['required', 'integer', 'exists' . Product::class . ',id'],
            'user_id' => ['required', 'integer', 'exists' . User::class . ',id'],
            'purchase_order_id' => ['required', 'integer', 'exists' . PurchaseOrder::class . ',id'],
            'order_item_id' => ['required', 'integer', 'exists' . OrderItem::class . ',id'],
            'type' => ['required', 'string'],
            'quantity' => ['required', 'numeric'],
            'balance' => ['required', 'numeric'],
            'reference' => ['required', 'numeric'],
            'note' => ['required', 'string'],
        ]);

        StockMovement::create([
            'product_id' => $data['product_id'],
            'user_id' => $data['user_id'],
            'purchase_order_id' => $data['purchase_order_id'],
            'order_item_id' => $data['order_item_id'],
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'balance' => $data['balance'],
            'reference' => $data['reference'],
            'note' => $data['note']
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockMovement $stockMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockMovement $stockMovement)
    {
        //
    }
}
