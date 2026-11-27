<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $search = trim($filters['q'] ?? '');

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            })
            ->when(isset($filters['category_id']), fn (Builder $query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()->orderBy('name')->pluck('name', 'id')->all();

        return view('products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => null,
            'categories' => $this->categoryOptions(),
            'units' => $this->unitOptions(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->payload();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($data): Product {
            $product = Product::create($data);
            $product->update(['sku' => 'PRD-'.str_pad($product->id, 6, '0', STR_PAD_LEFT)]);

            return $product;
        });

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'unit']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'categories' => $this->categoryOptions($product->category_id),
            'units' => $this->unitOptions($product->unit_id),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->payload();
        $oldImage = $product->image;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']);
        }

        $product->update($data);

        if (isset($data['image']) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['status' => 'inactive']);

        return redirect()->route('products.index')->with('success', 'Product deactivated.');
    }

    /**
     * @return array<string, string>
     */
    private function categoryOptions(?int $currentCategoryId = null): array
    {
        return Category::query()
            ->where(function (Builder $query) use ($currentCategoryId): void {
                $query->where('status', 'active');

                if ($currentCategoryId !== null) {
                    $query->orWhereKey($currentCategoryId);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function unitOptions(?int $currentUnitId = null): array
    {
        return Unit::query()
            ->where(function (Builder $query) use ($currentUnitId): void {
                $query->where('status', 'active');

                if ($currentUnitId !== null) {
                    $query->orWhereKey($currentUnitId);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
