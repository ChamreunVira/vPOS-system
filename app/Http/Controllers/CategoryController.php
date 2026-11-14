<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        $categories = Category::all();

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
        return $this->demo('Category deleted');
    }
}
