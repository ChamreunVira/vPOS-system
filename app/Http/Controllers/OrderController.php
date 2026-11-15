<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Customer;
use App\Support\MockData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('orders.index', ['orders' => MockData::orders()]);
    }

    // public function store(Request $request) {
    //     $data = $request->validate([
    //         'customer_id' => ['sometimes', 'integer', 'exists:'. Customer::class .',id'],
    //         'cashier_id' => ['required', 'integer', 'exists:'. Customer::class . ',id'],
    //         'status' => ['required', 'string', Rule::enum(OrderStatus::class),
    //         'subtotal' => ['required', 'numeric'],
    //         'discount_percent' => ['required', 'numeric'],
    //         // 'discount_amount' => ['required', ]
    //     ]);
    // }

    public function show(string $order)
    {
        return view('orders.show', ['order' => MockData::order((int) $order)]);
    }

    public function refund(Request $request, string $order)
    {
        return $this->demo('Refund recorded');
    }
}
