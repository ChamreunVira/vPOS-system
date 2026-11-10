<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        $products = MockData::products();

        return view('inventory.index', [
            'products' => $products,
            'movements' => MockData::stockMovements(),
            'lowStockProducts' => MockData::lowStockProducts(),
            'stockValue' => $products->sum(fn ($p) => $p['cost'] * $p['stock']),
        ]);
    }

    /** Handles stock in, stock out and adjustments from the same modal. */
    public function adjust(Request $request)
    {
        return $this->demo('Stock updated');
    }
}
