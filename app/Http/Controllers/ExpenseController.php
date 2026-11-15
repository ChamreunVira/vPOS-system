<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::with(['category', 'creator', 'paymentMethod'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        if ($search = $request->input('q')) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        $expenses = $query->paginate(20)->withQueryString();

        $thisMonthTotal = Expense::whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        /** @var array{name: string, total: float}|null $largestCategory */
        $largestCategory = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->selectRaw('expense_categories.name, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->first();

        $categories = ExpenseCategory::where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $paymentMethods = PaymentMethod::orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'thisMonthTotal' => (float) $thisMonthTotal,
            'largestCategory' => $largestCategory,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
        ]);

        Expense::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'expenses-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Date', 'Category', 'Description', 'Payment Method', 'Recorded By', 'Amount']);

            Expense::with(['category', 'creator', 'paymentMethod'])
                ->orderByDesc('expense_date')
                ->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $expense) {
                        fputcsv($handle, [
                            $expense->id,
                            $expense->expense_date->format('Y-m-d'),
                            $expense->category->name ?? '',
                            $expense->description ?? '',
                            $expense->paymentMethod->name ?? 'Cash',
                            $expense->creator->name ?? '',
                            number_format((float) $expense->amount, 2, '.', ''),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
