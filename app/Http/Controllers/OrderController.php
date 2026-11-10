<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('orders.index', ['orders' => MockData::orders()]);
    }

    public function show(string $order)
    {
        return view('orders.show', ['order' => MockData::order((int) $order)]);
    }

    public function refund(Request $request, string $order)
    {
        return $this->demo('Refund recorded');
    }
}
