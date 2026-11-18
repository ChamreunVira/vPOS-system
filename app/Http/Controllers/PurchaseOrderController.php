<?php

namespace App\Http\Controllers;

use App\Actions\Purchases\CancelPurchaseOrderAction;
use App\Actions\Purchases\CreatePurchaseOrderAction;
use App\Actions\Purchases\ReceivePurchaseOrderAction;
use App\Actions\Purchases\UpdatePurchaseOrderAction;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders with search, filters and pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $supplierId = $request->filled('supplier_id') ? $request->integer('supplier_id') : null;
        $date = $request->input('date');

        $query = PurchaseOrder::query()
            ->with(['supplier', 'creator', 'items.product'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('purchase_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function (Builder $q) use ($search): void {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($date, fn (Builder $query) => $query->whereDate('ordered_at', $date))
            ->latest('ordered_at')
            ->latest('id');

        $purchases = $query->paginate(15)->withQueryString();

        $suppliers = Supplier::query()->orderBy('name')->pluck('name', 'id')->all();

        $stats = [
            'total_spend' => PurchaseOrder::query()->where('status', PurchaseOrderStatus::Received->value)->sum('total'),
            'received_count' => PurchaseOrder::query()->where('status', PurchaseOrderStatus::Received->value)->count(),
            'pending_count' => PurchaseOrder::query()->where('status', PurchaseOrderStatus::Pending->value)->count(),
            'total_count' => PurchaseOrder::query()->count(),
        ];

        return view('purchases.index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'stats' => $stats,
            'statuses' => PurchaseOrderStatus::all(),
        ]);
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create(): View
    {
        $suppliers = Supplier::query()->where('status', 'active')->orderBy('name')->get();
        $products = Product::query()
            ->with('unit')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $productsLookup = $products->mapWithKeys(fn (Product $p) => [
            $p->id => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'cost' => (float) $p->cost,
                'stock' => (float) $p->stock,
                'unit_id' => $p->unit_id,
                'unit' => $p->unit?->abbreviation ?? $p->unit?->name ?? 'pc',
            ],
        ])->all();

        return view('purchases.form', [
            'purchase' => null,
            'suppliers' => $suppliers,
            'products' => $products,
            'productsLookup' => $productsLookup,
        ]);
    }

    /**
     * Store a newly created purchase order in storage.
     */
    public function store(Request $request, CreatePurchaseOrderAction $createPurchaseOrder): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,received'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);

        $purchaseOrder = $createPurchaseOrder->execute($validated, $request->user());

        $message = $purchaseOrder->isReceived()
            ? "Purchase order {$purchaseOrder->purchase_number} created and received. Stock levels updated."
            : "Purchase order {$purchaseOrder->purchase_number} created successfully.";

        return redirect()->route('purchases.show', $purchaseOrder)->with('success', $message);
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchase): View
    {
        $purchase->loadMissing([
            'supplier',
            'creator',
            'items.product.unit',
            'items.unit',
            'stockMovements.user',
        ]);

        return view('purchases.show', [
            'purchase' => $purchase,
        ]);
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(PurchaseOrder $purchase): View|RedirectResponse
    {
        if (! $purchase->canEdit()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', "Purchase order {$purchase->purchase_number} cannot be edited because it is {$purchase->status}.");
        }

        $purchase->loadMissing(['items.product.unit', 'supplier']);

        $suppliers = Supplier::query()->where('status', 'active')->orderBy('name')->get();
        $products = Product::query()
            ->with('unit')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $productsLookup = $products->mapWithKeys(fn (Product $p) => [
            $p->id => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'cost' => (float) $p->cost,
                'stock' => (float) $p->stock,
                'unit_id' => $p->unit_id,
                'unit' => $p->unit?->abbreviation ?? $p->unit?->name ?? 'pc',
            ],
        ])->all();

        return view('purchases.form', [
            'purchase' => $purchase,
            'suppliers' => $suppliers,
            'products' => $products,
            'productsLookup' => $productsLookup,
        ]);
    }

    /**
     * Update the specified purchase order in storage.
     */
    public function update(
        Request $request,
        PurchaseOrder $purchase,
        UpdatePurchaseOrderAction $updatePurchaseOrder
    ): RedirectResponse {
        if (! $purchase->canEdit()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', "Purchase order {$purchase->purchase_number} cannot be updated because it is {$purchase->status}.");
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ]);

        $purchaseOrder = $updatePurchaseOrder->execute($purchase, $validated);

        return redirect()->route('purchases.show', $purchaseOrder)
            ->with('success', "Purchase order {$purchaseOrder->purchase_number} updated successfully.");
    }

    /**
     * Remove the specified purchase order from storage.
     */
    public function destroy(PurchaseOrder $purchase): RedirectResponse
    {
        if (! $purchase->canDelete()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Received purchase orders cannot be deleted because stock movements are attached.');
        }

        $number = $purchase->purchase_number;
        $purchase->delete();

        return redirect()->route('purchases.index')
            ->with('success', "Purchase order {$number} deleted successfully.");
    }

    /**
     * Mark a pending purchase order as received and record stock movements.
     */
    public function receive(PurchaseOrder $purchase, ReceivePurchaseOrderAction $receivePurchaseOrder): RedirectResponse
    {
        $purchaseOrder = $receivePurchaseOrder->execute($purchase, auth()->user());

        return redirect()->route('purchases.show', $purchaseOrder)
            ->with('success', "Purchase order {$purchaseOrder->purchase_number} received. Products have been stocked into inventory.");
    }

    /**
     * Cancel a pending purchase order.
     */
    public function cancel(PurchaseOrder $purchase, CancelPurchaseOrderAction $cancelPurchaseOrder): RedirectResponse
    {
        $purchaseOrder = $cancelPurchaseOrder->execute($purchase);

        return redirect()->route('purchases.show', $purchaseOrder)
            ->with('success', "Purchase order {$purchaseOrder->purchase_number} has been cancelled.");
    }

    /**
     * Export purchase orders to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $supplierId = $request->filled('supplier_id') ? $request->integer('supplier_id') : null;
        $date = $request->input('date');

        $query = PurchaseOrder::query()
            ->with(['supplier', 'creator', 'items.product'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('purchase_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function (Builder $q) use ($search): void {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status && $status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($supplierId !== null, fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when($date, fn (Builder $query) => $query->whereDate('ordered_at', $date))
            ->latest('ordered_at')
            ->latest('id');

        $filename = 'purchases-export-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Purchase Number',
                'Date Ordered',
                'Date Received',
                'Supplier',
                'Status',
                'Items Count',
                'Total Amount',
                'Created By',
                'Notes',
            ]);

            $query->chunk(100, function ($chunk) use ($handle): void {
                /** @var PurchaseOrder $po */
                foreach ($chunk as $po) {
                    fputcsv($handle, [
                        $po->purchase_number,
                        $po->ordered_at?->format('Y-m-d H:i') ?? '',
                        $po->received_at?->format('Y-m-d H:i') ?? '',
                        $po->supplier?->name ?? 'N/A',
                        ucfirst($po->status),
                        $po->items_count,
                        $po->total,
                        $po->creator?->name ?? 'N/A',
                        $po->notes ?? '',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
