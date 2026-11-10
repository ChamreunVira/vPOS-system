<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('expenses.index', ['expenses' => MockData::expenses(), 'categories' => MockData::expenseCategories()]);
    }

    public function store(Request $request)
    {
        return $this->demo('Expense recorded');
    }

    public function update(Request $request, string $expense)
    {
        return $this->demo('Expense updated');
    }

    public function destroy(string $expense)
    {
        return $this->demo('Expense deleted');
    }
}
