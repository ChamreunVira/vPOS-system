<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('employees.index', ['employees' => MockData::employees(), 'roles' => MockData::roles()]);
    }

    public function create()
    {
        return view('employees.form', ['employee' => null, 'roles' => MockData::roles()['names']]);
    }

    public function store(Request $request)
    {
        return $this->demo('Employee created');
    }

    public function edit(string $employee)
    {
        $employee = MockData::employees()->firstWhere('id', (int) $employee) ?? abort(404);

        return view('employees.form', ['employee' => $employee, 'roles' => MockData::roles()['names']]);
    }

    public function update(Request $request, string $employee)
    {
        return $this->demo('Employee updated');
    }

    public function destroy(string $employee)
    {
        return $this->demo('Employee removed');
    }
}
