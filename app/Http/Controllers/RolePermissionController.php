<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    public function updateMatrix(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        DB::transaction(function () use ($validated): void {
            $selectedPermissions = $validated['permissions'] ?? [];

            foreach (Role::query()->get() as $role) {
                $role->permissions()->sync($selectedPermissions[$role->id] ?? []);
            }
        });

        return redirect()->route('employees.index')->with('success', 'Role permissions updated.');
    }
}
