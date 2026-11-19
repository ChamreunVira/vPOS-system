@php
    $isEdit = (bool) $purchase;
    $initialItems = [];

    if (old('items')) {
        foreach (old('items') as $oldItem) {
            $pId = $oldItem['product_id'] ?? null;
            $unitName = ($pId && isset($productsLookup[$pId])) ? $productsLookup[$pId]['unit'] : 'pc';
            $unitId = ($pId && isset($productsLookup[$pId])) ? $productsLookup[$pId]['unit_id'] : null;
            $initialItems[] = [
                'product_id' => $pId ? (int) $pId : '',
                'quantity' => isset($oldItem['quantity']) ? (float) $oldItem['quantity'] : 1,
                'unit_cost' => isset($oldItem['unit_cost']) ? (float) $oldItem['unit_cost'] : 0,
                'unit_id' => $unitId,
                'unit' => $unitName,
            ];
        }
    } elseif ($isEdit && $purchase->items->isNotEmpty()) {
        foreach ($purchase->items as $item) {
            $initialItems[] = [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'unit_id' => $item->unit_id ?? $item->product?->unit_id,
                'unit' => $item->unit?->abbreviation ?? $item->unit?->name ?? $item->product?->unit?->abbreviation ?? 'pc',
            ];
        }
    }

    if (empty($initialItems)) {
        $initialItems = [
            ['product_id' => '', 'quantity' => 1, 'unit_cost' => 0.00, 'unit_id' => null, 'unit' => 'pc']
        ];
    }
@endphp

<x-layouts.app :title="$isEdit ? 'Edit ' . $purchase->purchase_number : 'New purchase order'">
    <x-ui.flash />

    <x-ui.page-header :title="$isEdit ? 'Edit purchase order' : 'New purchase order'"
                      :subtitle="$isEdit ? $purchase->purchase_number . ' · ' . ($purchase->supplier?->name ?? '') : 'Create a purchase order with items, quantities and costs.'"
                      :back="$isEdit ? route('purchases.show', $purchase) : route('purchases.index')">
        <x-ui.button :href="$isEdit ? route('purchases.show', $purchase) : route('purchases.index')" variant="secondary">Cancel</x-ui.button>
        <x-ui.button type="submit" form="purchase-form" icon="check">
            {{ $isEdit ? 'Update purchase order' : 'Save purchase order' }}
        </x-ui.button>
    </x-ui.page-header>

    <div x-data="{
        products: @js($productsLookup),
        items: @js($initialItems),
        status: @js(old('status', $purchase?->status ?? 'pending')),
        updateCost: @js(old('update_cost', true)),
        addItem() {
            this.items.push({
                product_id: '',
                quantity: 1,
                unit_cost: 0,
                unit_id: null,
                unit: 'pc'
            });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        onProductChange(index) {
            const pId = this.items[index].product_id;
            if (pId && this.products[pId]) {
                const prod = this.products[pId];
                this.items[index].unit_cost = Number(prod.cost) || 0;
                this.items[index].unit_id = prod.unit_id || null;
                this.items[index].unit = prod.unit || 'pc';
            }
        },
        lineTotal(item) {
            const qty = Number(item.quantity) || 0;
            const cost = Number(item.unit_cost) || 0;
            return Math.round(qty * cost * 100) / 100;
        },
        grandTotal() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },
        totalQty() {
            return this.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
        }
    }">
        <form id="purchase-form" method="POST"
              action="{{ $isEdit ? route('purchases.update', $purchase) : route('purchases.store') }}"
              class="space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Order Meta Card --}}
                <div class="space-y-6 lg:col-span-1">
                    <x-ui.card title="Order details">
                        <div class="space-y-4">
                            <div>
                                <label for="supplier_id" class="block text-sm font-medium text-neutral-900">
                                    Supplier <span class="text-red-500">*</span>
                                </label>
                                <select id="supplier_id" name="supplier_id" required
                                        class="mt-1 h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                    <option value="">Select a supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected((int) old('supplier_id', $purchase?->supplier_id) === (int) $supplier->id)>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <p class="mt-1 text-xs font-medium text-neutral-950">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-form.input label="Ordered date" name="ordered_at" type="date"
                                          :value="old('ordered_at', $purchase?->ordered_at?->format('Y-m-d') ?? now()->format('Y-m-d'))" />

                            @if (! $isEdit)
                                <div class="space-y-3 rounded-lg border border-line bg-neutral-50/50 p-3">
                                    <label class="block text-sm font-medium text-neutral-900">Receipt status</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-sm text-neutral-700">
                                            <input type="radio" name="status" value="pending" x-model="status" class="text-neutral-900 focus:ring-neutral-900">
                                            <span>Save as Pending (Receive later)</span>
                                        </label>
                                        <label class="flex items-center gap-2 text-sm text-neutral-700">
                                            <input type="radio" name="status" value="received" x-model="status" class="text-neutral-900 focus:ring-neutral-900">
                                            <span>Receive immediately (Stock in now)</span>
                                        </label>
                                    </div>

                                    <div x-show="status === 'received'" x-cloak class="border-t border-line pt-2">
                                        <label class="flex items-start gap-2 text-xs text-neutral-700">
                                            <input type="hidden" name="update_cost" value="0">
                                            <input type="checkbox" name="update_cost" value="1" x-model="updateCost" class="mt-0.5 rounded text-neutral-900 focus:ring-neutral-900">
                                            <span>
                                                <strong class="font-medium text-neutral-900">Update product cost</strong>
                                                <span class="block text-neutral-500">Update the product catalog cost to match this purchase order's unit cost.</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <x-form.textarea label="Notes" name="notes" rows="3"
                                             placeholder="Reference number, delivery notes, terms..."
                                             :value="old('notes', $purchase?->notes)" />
                        </div>
                    </x-ui.card>
                </div>

                {{-- Items Card --}}
                <div class="space-y-6 lg:col-span-2">
                    <x-ui.card title="Purchase items" :padded="false">
                        <div class="p-4 sm:flex sm:items-center sm:justify-between border-b border-line">
                            <p class="text-xs text-neutral-500">Add products, ordered quantities and negotiated unit costs.</p>
                            <x-ui.button type="button" variant="secondary" size="sm" icon="plus" @click="addItem()">
                                Add item
                            </x-ui.button>
                        </div>

                        @error('items')
                            <div class="p-4 text-xs font-medium text-red-600 bg-red-50 border-b border-red-200">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-line bg-neutral-50 text-xs font-medium text-neutral-500">
                                    <tr>
                                        <th class="px-4 py-3">Product</th>
                                        <th class="w-24 px-4 py-3">Unit</th>
                                        <th class="w-28 px-4 py-3 text-right">Quantity</th>
                                        <th class="w-32 px-4 py-3 text-right">Unit cost</th>
                                        <th class="w-32 px-4 py-3 text-right">Total</th>
                                        <th class="w-12 px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="hover:bg-neutral-50/50">
                                            <td class="px-4 py-2.5">
                                                <select :name="`items[${index}][product_id]`"
                                                        x-model="item.product_id"
                                                        @change="onProductChange(index)"
                                                        required
                                                        class="h-9 w-full rounded-lg border border-neutral-300 bg-white px-2.5 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                                    <option value="">Select product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">
                                                            {{ $product->name }} ({{ $product->sku }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" :name="`items[${index}][unit_id]`" :value="item.unit_id">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-block rounded bg-neutral-100 px-2 py-1 text-xs font-medium text-neutral-600"
                                                      x-text="item.unit || 'pc'"></span>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <input type="number" step="any" min="0.0001" required
                                                       :name="`items[${index}][quantity]`"
                                                       x-model.number="item.quantity"
                                                       class="font-money h-9 w-full rounded-lg border border-neutral-300 px-2 text-right text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-neutral-400">$</span>
                                                    <input type="number" step="0.01" min="0" required
                                                           :name="`items[${index}][unit_cost]`"
                                                           x-model.number="item.unit_cost"
                                                           class="font-money h-9 w-full rounded-lg border border-neutral-300 pl-6 pr-2 text-right text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-money font-medium text-neutral-900">
                                                $<span x-text="lineTotal(item).toFixed(2)"></span>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <button type="button" @click="removeItem(index)"
                                                        :disabled="items.length <= 1"
                                                        class="rounded p-1 text-neutral-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-neutral-400">
                                                    <x-ui.icon name="trash" class="size-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="border-t border-line bg-neutral-50/50">
                                    <tr>
                                        <td colspan="2" class="px-4 py-3 text-sm font-medium text-neutral-700">
                                            Total
                                        </td>
                                        <td class="px-4 py-3 text-right font-money font-semibold text-neutral-900">
                                            <span x-text="totalQty()"></span>
                                        </td>
                                        <td></td>
                                        <td class="px-4 py-3 text-right font-money text-base font-bold text-neutral-950">
                                            $<span x-text="grandTotal().toFixed(2)"></span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
