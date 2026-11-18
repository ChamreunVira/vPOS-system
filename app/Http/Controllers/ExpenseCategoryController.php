<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('expenses.index', ['tab' => 'categories']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
        ]);

        $status = $request->has('status')
            ? ($request->boolean('status') ? 'active' : 'inactive')
            : 'active';

        ExpenseCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
        ]);

        return redirect()->route('expenses.index', ['tab' => 'categories'])
            ->with('success', 'Expense category created successfully.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'name')->ignore($expenseCategory->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
        ]);

        $status = $request->has('status')
            ? ($request->boolean('status') ? 'active' : 'inactive')
            : $expenseCategory->status;

        $expenseCategory->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
        ]);

        return redirect()->route('expenses.index', ['tab' => 'categories'])
            ->with('success', 'Expense category updated successfully.');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            return redirect()->route('expenses.index', ['tab' => 'categories'])
                ->with('error', 'Cannot delete category because it has recorded expenses. You can deactivate it instead.');
        }

        $expenseCategory->delete();

        return redirect()->route('expenses.index', ['tab' => 'categories'])
            ->with('success', 'Expense category deleted.');
    }
}
