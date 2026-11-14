<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = User::with('role')->paginate(10);
        $roles = Role::with('permissions')->get();
        $permissionsByModule = Permission::query()->get()->groupBy('module');

        return view('employees.index', compact('employees', 'roles', 'permissionsByModule'));
    }

    public function create(): View
    {
        return view('employees.form', ['employee' => null, 'roles' => Role::query()->pluck('name', 'id')->all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'lowercase', 'max:255', 'email', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['nullable', 'boolean'],
        ]);

        User::create([...$data, 'status' => $request->boolean('status') ? 'active' : 'inactive']);

        return redirect()->route('employees.index')->with('success', 'Employee created.');
    }

    public function edit(User $employee): View
    {
        return view('employees.form', ['employee' => $employee, 'roles' => Role::query()->pluck('name', 'id')->all()]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'lowercase', 'max:255', 'email', Rule::unique(User::class)->ignore($employee)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['nullable', 'boolean'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $status = $request->boolean('status') ? 'active' : 'inactive';

        if (Auth::user()?->is($employee) && $status === 'inactive') {
            throw ValidationException::withMessages([
                'status' => 'You cannot deactivate your own account.',
            ]);
        }

        $employee->update([...$data, 'status' => $status]);

        return redirect()->route('employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(User $employee): RedirectResponse
    {
        if (Auth::user()?->is($employee)) {
            throw ValidationException::withMessages([
                'employee' => 'You cannot deactivate your own account.',
            ]);
        }

        $employee->update(['status' => 'inactive']);

        return redirect()->route('employees.index')->with('success', 'Employee deactivated. Their sales history has been kept.');
    }
}
