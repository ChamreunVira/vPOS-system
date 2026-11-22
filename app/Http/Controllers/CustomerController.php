<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable'],
        ]);

        $data['status'] = $request->boolean('status') ? 'active' : 'inactive';

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['orders' => function ($query): void {
            $query->with('payments.paymentMethod')->latest('place_at');
        }]);

        $totalSpent = (float) $customer->orders->sum('total');

        return view('customers.show', compact('customer', 'totalSpent'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable'],
        ]);

        $data['status'] = $request->boolean('status') ? 'active' : 'inactive';

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
