<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->latest()
            ->paginate(15);

        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn(StockMovement $movement) => [
                'at' => $movement->created_at,
                'product' => ['name' => $movement->product?->name ?? 'Unknown product'],
                'note' => $movement->note,
                'type' => $movement->type,
                'reference' => $movement->reference,
                'user' => $movement->user?->name ?? 'Unknown user',
                'quantity' => $movement->quantity,
                'balance' => $movement->balance,
            ]);

        $lowStockProducts = Product::query()
            ->with(['category', 'unit'])
            ->whereColumn('stock', '<=', 'min_stock')
            ->get();

        $stockValue = Product::query()
            ->selectRaw('COALESCE(SUM(cost * stock), 0) as total')
            ->value('total');

        return view('inventory.index', [
            'products' => $products,
            'movements' => $movements,
            'lowStockProducts' => $lowStockProducts,
            'stockValue' => $stockValue,
        ]);
    }

    /** Handles stock in, stock out and adjustments from the same modal. */
    public function adjust(Request $request)
    {
        return $this->demo('Stock updated');
    }
}
