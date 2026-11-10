<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('customers.index', ['customers' => MockData::customers()]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => null]);
    }

    public function store(Request $request)
    {
        return $this->demo('Customer created');
    }

    public function show(string $customer)
    {
        $customer = MockData::customer((int) $customer);
        $orders = MockData::orders()->take(4);

        return view('customers.show', compact('customer', 'orders'));
    }

    public function edit(string $customer)
    {
        return view('customers.form', ['customer' => MockData::customer((int) $customer)]);
    }

    public function update(Request $request, string $customer)
    {
        return $this->demo('Customer updated');
    }

    public function destroy(string $customer)
    {
        return $this->demo('Customer deleted');
    }
}
