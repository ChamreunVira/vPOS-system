<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('categories.index', ['categories' => MockData::categories()]);
    }

    public function store(Request $request)
    {
        return $this->demo('Category created');
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
