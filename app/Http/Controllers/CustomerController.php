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
        $customers = Customer::all()->load('orders');

        $customers = $customers->map(fn($customer) => [
            ...$customer->toArray(),
            'total_spent' => array_sum(array_map(fn($item) => $item['total'], $customer['orders']->toArray())),
            'orders_count' => count($customer['orders']),
            'last_visit' => $customer['created_at']->format('M j, Y')
        ]);

        return view('customers.index', compact('customers'));
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

        $customer = Customer::query()
            ->find((int) $customer)
            ->load('orders');

        $total = array_map(fn($item) => $item['total'], $customer['orders']->toArray());
        $totalSpent = array_sum($total);

        return view('customers.show', compact('customer', 'totalSpent'));
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
