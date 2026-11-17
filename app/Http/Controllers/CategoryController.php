<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Category;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use RespondsWithDemoNotice;

    public function index(Request $request)
    {
        $search = trim($request->input('q', ''));

        $categories = Category::query()
            ->when(
                $search !== '',
                fn(Builder $query) =>
                $query->where('name', 'like', '%' . $search . '%')
            )->get();

        return view('categories.index', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
        ]);


        Category::create([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        return redirect()->route('categories.index')->with('status', 'Category created.');
    }

    public function update(Request $request, string $category)
    {
        return $this->demo('Category updated');
    }

    public function destroy(string $category)
    {
        $category = Category::find($category);
        $category->delete();

        return redirect()->route('categories.index')->with(['success', 'Category deleted']);
    }
}
