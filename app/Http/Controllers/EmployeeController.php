<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Role;
use App\Models\User;
use App\Support\MockData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;

class EmployeeController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        $employees = User::all();

        return view('employees.index', ['employees' => $employees, 'roles' => MockData::roles()]);
    }

    public function create()
    {
        return view('employees.form', ['employee' => null, 'roles' => MockData::roles()['names']]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'lowercase', 'max:255', 'email', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:'.Role::class],
        ]);

        $role = Role::where('name', $data['role_id']);
        dd($role);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $role->id,
        ]);

        return $this->demo('Employee created');
    }

    public function edit(string $employee)
    {
        $employee = User::find($employee);

        return view('employees.form', ['employee' => $employee, 'roles' => MockData::roles()['names']]);
    }

    public function update(Request $request, string $employee)
    {
        $employee = User::find($employee);
        $role = Role::where('name', $employee->name);
        dd($role);
        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $employee->password,
        ]);

        return $this->demo('Employee updated');
    }

    public function destroy(string $employee)
    {

        return $this->demo('Employee removed');
    }
}
