<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithDemoNotice;
use App\Support\MockData;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use RespondsWithDemoNotice;

    public function index()
    {
        return view('products.index', ['products' => MockData::products(), 'categories' => MockData::categories()]);
    }

    public function create()
    {
        return view('products.form', ['product' => null, 'categories' => MockData::categories()->where('status', 'active')]);
    }

    public function store(Request $request)
    {
        return $this->demo('Product created');
    }

    public function show(string $product)
    {
        $product = MockData::product((int) $product);
        $movements = MockData::stockMovements()->filter(fn ($m) => $m['product']['id'] === $product['id']);

        return view('products.show', compact('product', 'movements'));
    }

    public function edit(string $product)
    {
        return view('products.form', ['product' => MockData::product((int) $product), 'categories' => MockData::categories()->where('status', 'active')]);
    }

    public function update(Request $request, string $product)
    {
        return $this->demo('Product updated');
    }

    public function destroy(string $product)
    {
        return $this->demo('Product deleted');
    }
}
