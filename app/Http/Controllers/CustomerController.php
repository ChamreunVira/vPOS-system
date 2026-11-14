<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Customer;
use App\Support\MockData;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        $customers = Customer::all();

        return view('customers.index', ['customers' => $customers]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase'],
            'address' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
        ]);

        Customer::Create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address' => $data['address'],
            'notes' => $data['notes'],
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer created.');
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
