<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $categories = Category::query()
            ->withCount('products')
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                })
            )
            ->orderBy('name')
            ->get();

        return view('categories.index', ['categories' => $categories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
        ]);

        $data['status'] = $request->boolean('status') ? 'active' : 'inactive';

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
        ]);

        $data['status'] = $request->boolean('status') ? 'active' : 'inactive';

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
