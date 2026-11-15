<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\AdjustStockAction;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    /**
     * Display inventory stock levels, low stock alerts, and movement history.
     */
    public function index(Request $request): View
    {
        $search = trim($request->input('q', ''));
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;
        $stockStatus = $request->input('stock_status');

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            })
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($stockStatus === 'low_stock', fn (Builder $query) => $query->lowStock())
            ->when($stockStatus === 'out_of_stock', fn (Builder $query) => $query->outOfStock())
            ->when($stockStatus === 'in_stock', fn (Builder $query) => $query->inStock())
            ->orderBy('name')
            ->paginate(15, ['*'], 'products_page')
            ->withQueryString();

        $movementProductId = $request->filled('movement_product_id') ? $request->integer('movement_product_id') : null;
        $movementType = $request->input('movement_type');
        $movementSearch = trim($request->input('movement_q', ''));

        $movements = StockMovement::query()
            ->with(['product.unit', 'user'])
            ->when($movementProductId !== null, fn (Builder $query) => $query->where('product_id', $movementProductId))
            ->when($movementType && $movementType !== 'all', fn (Builder $query) => $query->where('type', $movementType))
            ->when($movementSearch !== '', function (Builder $query) use ($movementSearch): void {
                $query->where(function (Builder $query) use ($movementSearch): void {
                    $query->where('reference', 'like', '%'.$movementSearch.'%')
                        ->orWhere('note', 'like', '%'.$movementSearch.'%')
                        ->orWhereHas('product', function (Builder $query) use ($movementSearch): void {
                            $query->where('name', 'like', '%'.$movementSearch.'%')
                                ->orWhere('sku', 'like', '%'.$movementSearch.'%');
                        });
                });
            })
            ->latest()
            ->paginate(15, ['*'], 'movements_page')
            ->withQueryString();

        $lowStockProducts = Product::query()
            ->with(['category', 'unit'])
            ->where(function (Builder $query): void {
                $query->lowStock()->orWhere(fn (Builder $q) => $q->outOfStock());
            })
            ->get();

        $stockValue = Product::query()
            ->selectRaw('COALESCE(SUM(cost * stock), 0) as total')
            ->value('total') ?? 0;

        $categories = Category::query()->orderBy('name')->pluck('name', 'id')->all();

        $allProducts = Product::query()
            ->with('unit')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock', 'unit_id']);

        $productLookup = $allProducts->mapWithKeys(fn (Product $p) => [
            $p->id => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => (float) $p->stock,
                'unit' => $p->unit?->abbreviation ?? $p->unit?->name ?? 'pc',
            ],
        ])->all();

        $outOfStockCount = Product::query()->outOfStock()->count();
        $lowStockCount = Product::query()->lowStock()->count();
        $totalProductsCount = Product::count();

        $activeTab = $request->input('tab', 'overview');

        return view('inventory.index', [
            'products' => $products,
            'movements' => $movements,
            'lowStockProducts' => $lowStockProducts,
            'stockValue' => $stockValue,
            'categories' => $categories,
            'allProducts' => $allProducts,
            'productLookup' => $productLookup,
            'outOfStockCount' => $outOfStockCount,
            'lowStockCount' => $lowStockCount,
            'totalProductsCount' => $totalProductsCount,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Handles stock in, stock out, damage and adjustments from modal or form.
     */
    public function adjust(Request $request, AdjustStockAction $adjustStock): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', 'in:stock_in,stock_out,adjustment,in,out,purchase,sale,return,damage'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'adjustment_mode' => ['nullable', 'string', 'in:count,delta'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $movement = $adjustStock->execute(
            product: $product,
            type: $validated['type'],
            quantity: $validated['quantity'],
            reason: $validated['reason'] ?? null,
            note: $validated['note'] ?? null,
            adjustmentMode: $validated['adjustment_mode'] ?? 'count',
            user: $request->user(),
        );

        return redirect()->route('inventory.index', ['tab' => 'movements'])
            ->with('success', "Stock for {$product->name} successfully updated. New balance: {$movement->balance}.");
    }

    /**
     * Export current inventory stock report to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = trim($request->input('q', ''));
        $categoryId = $request->filled('category_id') ? $request->integer('category_id') : null;
        $stockStatus = $request->input('stock_status');

        $query = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            })
            ->when($categoryId !== null, fn (Builder $query) => $query->where('category_id', $categoryId))
            ->when($stockStatus === 'low_stock', fn (Builder $query) => $query->lowStock())
            ->when($stockStatus === 'out_of_stock', fn (Builder $query) => $query->outOfStock())
            ->when($stockStatus === 'in_stock', fn (Builder $query) => $query->inStock())
            ->orderBy('name');

        $filename = 'inventory-stock-report-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Product ID', 'Name', 'SKU', 'Barcode', 'Category', 'Unit', 'Cost', 'Price', 'In Stock', 'Min Stock', 'Stock Value', 'Status']);

            $query->chunk(100, function ($chunk) use ($handle): void {
                /** @var Product $product */
                foreach ($chunk as $product) {
                    fputcsv($handle, [
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->barcode ?? '',
                        $product->category?->name ?? '',
                        $product->unit?->abbreviation ?? $product->unit?->name ?? '',
                        $product->cost,
                        $product->price,
                        $product->stock,
                        $product->min_stock,
                        round((float) $product->cost * (float) $product->stock, 2),
                        ucfirst(str_replace('_', ' ', $product->stock_level)),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Redirect to inventory movements tab filtered by product.
     */
    public function productMovements(Product $product): RedirectResponse
    {
        return redirect()->route('inventory.index', [
            'tab' => 'movements',
            'movement_product_id' => $product->id,
        ]);
    }
}
