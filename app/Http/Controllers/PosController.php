<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        return view('pos.index', [
            'products' => MockData::products()->values(),
            'customers' => MockData::customers()->where('status', 'active')->map(fn ($c) => ['id' => $c['id'], 'name' => $c['name']])->values(),
            'nextInvoice' => MockData::orders()->max('id') + 1,
        ]);
    }

    public function checkout(Request $request)
    {
        // TODO: validate the cart, create the order + payment, reduce stock, return the receipt data as JSON.
        return response()->json(['message' => 'Demo only: implement checkout here.']);
    }
}
