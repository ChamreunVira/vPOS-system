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
        // 1. Expenses query with filters
        $expenseQuery = Expense::with(['category', 'creator', 'paymentMethod'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('q'))) {
            $expenseQuery->where('description', 'like', "%{$search}%");
        }

        if ($categoryId = $request->input('category_id')) {
            $expenseQuery->where('expense_category_id', $categoryId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $expenseQuery->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $expenseQuery->whereDate('expense_date', '<=', $dateTo);
        }

        $expenses = $expenseQuery->paginate(20, ['*'], 'expenses_page')->withQueryString();

        // 2. Expense stats
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

        // 3. Categories tab query with filters
        $categoryQuery = ExpenseCategory::query()
            ->withCount('expenses')
            ->withSum('expenses', 'amount')
            ->orderBy('name');

        if ($catSearch = trim((string) $request->input('cat_q'))) {
            $categoryQuery->where(function ($q) use ($catSearch): void {
                $q->where('name', 'like', "%{$catSearch}%")
                    ->orWhere('description', 'like', "%{$catSearch}%");
            });
        }

        if ($catStatus = $request->input('cat_status')) {
            $categoryQuery->where('status', $catStatus);
        }

        $categoryItems = $categoryQuery->get();

        $totalCategories = ExpenseCategory::count();
        $activeCategoriesCount = ExpenseCategory::where('status', 'active')->count();

        // 4. Form dropdown lookups
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
            'categoryItems' => $categoryItems,
            'paymentMethods' => $paymentMethods,
            'thisMonthTotal' => (float) $thisMonthTotal,
            'largestCategory' => $largestCategory,
            'totalCategories' => $totalCategories,
            'activeCategoriesCount' => $activeCategoriesCount,
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

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Date', 'Category', 'Description', 'Payment Method', 'Recorded By', 'Amount']);

            Expense::with(['category', 'creator', 'paymentMethod'])
                ->orderByDesc('expense_date')
                ->chunk(200, function ($rows) use ($handle): void {
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
