<?php

namespace App\Http\Controllers;

use App\Actions\Orders\CreatePosOrderAction;
use App\Http\Requests\CheckoutRequest;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PosController extends Controller
{
    /**
     * Display the point of sale page.
     */
    public function index(): View
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $customers = Customer::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pos.index', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    /**
     * Process checkout for the POS cart.
     */
    public function checkout(CheckoutRequest $request, CreatePosOrderAction $createPosOrder): JsonResponse
    {
        $cashier = $request->user();

        if ($cashier === null) {
            abort(401);
        }

        $receipt = $createPosOrder->execute($request->validated(), $cashier);

        return response()->json($receipt);
    }
}
