<x-layouts.app title="Inventory">
    @php
        $typeLabels = [
            'stock_in' => 'Stock in',
            'stock_out' => 'Stock out',
            'adjustment' => 'Adjustment',
        ];
    @endphp

    <div x-data="{
        products: {{ Js::from($productLookup) }},
        form: {
            product_id: '',
            type: 'stock_in',
            quantity: 1,
            adjustment_mode: 'count',
            reason: 'Supplier delivery',
            note: ''
        },
        open(productId = '', type = 'stock_in', adjustmentMode = 'count') {
            const pId = productId ? String(productId) : '';
            const current = pId && this.products[pId] ? this.products[pId].stock : 0;
            this.form = {
                product_id: pId,
                type: type,
                quantity: type === 'adjustment' ? current : 1,
                adjustment_mode: adjustmentMode,
                reason: type === 'stock_in' ? 'Supplier delivery' : (type === 'stock_out' ? 'Damaged' : 'Stock count correction'),
                note: ''
            };
            $dispatch('open-modal', 'stock-adjust');
        },
        currentStock() {
            return (this.form.product_id && this.products[this.form.product_id]) ? this.products[this.form.product_id].stock : 0;
        },
        productUnit() {
            return (this.form.product_id && this.products[this.form.product_id]) ? this.products[this.form.product_id].unit : 'pc';
        },
        calculatedBalance() {
            const current = this.currentStock();
            const qty = Number(this.form.quantity) || 0;
            if (this.form.type === 'stock_in') {
                return Math.round((current + qty) * 10000) / 10000;
            }
            if (this.form.type === 'stock_out') {
                return Math.round((current - qty) * 10000) / 10000;
            }
            if (this.form.type === 'adjustment') {
                if (this.form.adjustment_mode === 'count') {
                    return Math.round(qty * 10000) / 10000;
                }
                return Math.round((current + qty) * 10000) / 10000;
            }
            return current;
        },
        calculatedDiff() {
            const current = this.currentStock();
            const qty = Number(this.form.quantity) || 0;
            if (this.form.type === 'stock_in') {
                return '+' + qty;
            }
            if (this.form.type === 'stock_out') {
                return '-' + qty;
            }
            if (this.form.type === 'adjustment') {
                const diff = this.form.adjustment_mode === 'count' ? (qty - current) : qty;
                return (diff > 0 ? '+' : '') + (Math.round(diff * 10000) / 10000);
            }
            return '0';
        }
    }">
        <x-ui.page-header title="Inventory" subtitle="Know what is on the shelf and what to reorder.">
            <x-ui.button :href="route('inventory.export', request()->only(['q', 'category_id', 'stock_status']))" variant="secondary" icon="download">Export stock report</x-ui.button>
            <x-ui.button icon="plus" @click="open()">Update stock</x-ui.button>
        </x-ui.page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Products" icon="package">{{ $totalProductsCount }}</x-ui.stat-card>
            <x-ui.stat-card label="Stock value (at cost)" icon="wallet"><x-ui.money :amount="$stockValue" /></x-ui.stat-card>
            <x-ui.stat-card label="Low stock" icon="alert">{{ $lowStockCount }}</x-ui.stat-card>
            <x-ui.stat-card label="Out of stock" icon="x">{{ $outOfStockCount }}</x-ui.stat-card>
        </div>

        @if ($lowStockProducts->isNotEmpty())
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-neutral-950 bg-white p-4 text-sm">
                <div class="flex items-center gap-3">
                    <x-ui.icon name="alert" class="size-5 shrink-0 text-amber-600" />
                    <p><span class="font-medium">{{ $lowStockProducts->count() }} {{ \Illuminate\Support\Str::plural('product', $lowStockProducts->count()) }} need attention.</span> Reorder before running out.</p>
                </div>
                <x-ui.button :href="route('inventory.index', ['stock_status' => 'low_stock'])" variant="secondary" size="sm">View low stock</x-ui.button>
            </div>
        @endif

        <div class="mt-6">
            <x-ui.tabs :tabs="['overview' => 'Stock overview', 'movements' => 'Movement history']" :default="$activeTab">
                <x-ui.tab-panel name="overview">
                    <x-ui.card :padded="false">
                        <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
                            <input type="hidden" name="tab" value="overview">
                            <x-ui.search class="min-w-56 flex-1" placeholder="Search by name, SKU or barcode" />
                            <x-form.select label="Category" name="category_id" class="w-48" placeholder="All categories" :options="$categories" :value="request('category_id')" />
                            <x-form.select label="Stock level" name="stock_status" class="w-40" placeholder="All stock levels" :options="['in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock']" :value="request('stock_status')" />
                            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
                            @if (request()->hasAny(['q', 'category_id', 'stock_status']))
                                <x-ui.button :href="route('inventory.index')" variant="ghost">Clear</x-ui.button>
                            @endif
                        </form>

                        <x-ui.table>
                            <x-slot:head>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th class="text-right">In stock</th>
                                    <th class="text-right">Minimum</th>
                                    <th class="text-right">Cost</th>
                                    <th class="text-right">Stock value</th>
                                    <th>Status</th>
                                    <th class="text-right"><span class="sr-only">Actions</span></th>
                                </tr>
                            </x-slot:head>
                            @forelse ($products as $product)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3 font-medium">
                                            <x-ui.product-thumb :product="$product" size="sm" />
                                            <div>
                                                <a href="{{ route('products.show', $product) }}" class="hover:underline">{{ $product->name }}</a>
                                                @if ($product->barcode)
                                                    <p class="text-xs text-neutral-500 font-normal">Barcode: {{ $product->barcode }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="font-money text-neutral-600">{{ $product->sku }}</td>
                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                    <td class="text-right font-money font-semibold">
                                        {{ $product->stock }} <span class="text-xs font-normal text-neutral-500">{{ $product->unit?->abbreviation ?? $product->unit?->name ?? 'pc' }}</span>
                                    </td>
                                    <td class="text-right font-money text-neutral-600">
                                        {{ $product->min_stock }} <span class="text-xs font-normal text-neutral-500">{{ $product->unit?->abbreviation ?? $product->unit?->name ?? 'pc' }}</span>
                                    </td>
                                    <td class="text-right text-neutral-600"><x-ui.money :amount="$product->cost" /></td>
                                    <td class="text-right font-money"><x-ui.money :amount="$product->cost * $product->stock" /></td>
                                    <td><x-ui.status :status="$product->stock_level" /></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <x-ui.button variant="secondary" size="sm" icon="arrow-down-left" :data-id="$product->id" @click="open({{ $product->id }}, 'stock_in')">In</x-ui.button>
                                            <x-ui.button variant="secondary" size="sm" icon="arrow-up-right" :data-id="$product->id" @click="open({{ $product->id }}, 'stock_out')">Out</x-ui.button>
                                            <x-ui.button variant="ghost" size="sm" :data-id="$product->id" @click="open({{ $product->id }}, 'adjustment')">Adjust</x-ui.button>
                                            <x-ui.button :href="route('inventory.index', ['tab' => 'movements', 'movement_product_id' => $product->id])" variant="ghost" size="sm" icon="layers" title="Movement history" aria-label="View history" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-12 text-center">
                                        <x-ui.empty-state icon="package" title="No inventory items found" description="Try clearing your filters or create new products." />
                                    </td>
                                </tr>
                            @endforelse
                        </x-ui.table>
                        <div class="border-t border-line px-5 py-3">{{ $products->links() }}</div>
                    </x-ui.card>
                </x-ui.tab-panel>

                <x-ui.tab-panel name="movements">
                    <x-ui.card :padded="false">
                        <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
                            <input type="hidden" name="tab" value="movements">
                            <x-form.select label="Product" name="movement_product_id" class="min-w-56 flex-1" placeholder="All products" :options="$allProducts->pluck('name', 'id')->all()" :value="request('movement_product_id')" />
                            <x-form.select label="Movement type" name="movement_type" class="w-44" placeholder="All types" :options="['purchase' => 'Purchase', 'sale' => 'Sale', 'return' => 'Return', 'adjustment' => 'Adjustment', 'damage' => 'Damage']" :value="request('movement_type')" />
                            <div class="w-56">
                                <x-ui.search placeholder="Reference or note" name="movement_q" :value="request('movement_q')" />
                            </div>
                            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
                            @if (request()->hasAny(['movement_product_id', 'movement_type', 'movement_q']))
                                <x-ui.button :href="route('inventory.index', ['tab' => 'movements'])" variant="ghost">Clear</x-ui.button>
                            @endif
                        </form>

                        <x-ui.table>
                            <x-slot:head>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>By</th>
                                    <th class="text-right">Change</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </x-slot:head>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap text-neutral-600">{{ $movement->created_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                    <td>
                                        <div class="font-medium text-neutral-900">{{ $movement->product?->name ?? 'Unknown product' }}</div>
                                        @if ($movement->note)
                                            <p class="text-xs text-neutral-500 font-normal">{{ $movement->note }}</p>
                                        @endif
                                    </td>
                                    <td><x-ui.status :status="$movement->type" /></td>
                                    <td class="font-mono text-xs text-neutral-600">{{ $movement->reference ?? '—' }}</td>
                                    <td>{{ $movement->user?->name ?? 'System' }}</td>
                                    <td class="text-right font-money font-medium {{ $movement->quantity > 0 ? 'text-emerald-700' : ($movement->quantity < 0 ? 'text-red-700' : 'text-neutral-500') }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="text-right font-money font-semibold">{{ $movement->balance }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <x-ui.empty-state icon="layers" title="No stock movements recorded" description="Stock changes from orders, deliveries, and adjustments will appear here." />
                                    </td>
                                </tr>
                            @endforelse
                        </x-ui.table>
                        <div class="border-t border-line px-5 py-3">{{ $movements->links() }}</div>
                    </x-ui.card>
                </x-ui.tab-panel>
            </x-ui.tabs>
        </div>

        <x-ui.modal name="stock-adjust" title="Update stock" description="Record stock coming in, going out, or a physical count correction.">
            <form id="stock-form" method="POST" action="{{ route('inventory.adjust') }}" class="space-y-4">
                @csrf
                <x-form.select label="Product" name="product_id" placeholder="Choose a product" x-model="form.product_id" :options="$allProducts->pluck('name', 'id')->all()" required />

                <div x-show="form.product_id" class="rounded-lg bg-neutral-50 p-3 text-sm flex items-center justify-between border border-line">
                    <span class="text-neutral-600">Current stock:</span>
                    <span class="font-semibold text-neutral-900">
                        <span x-text="currentStock()"></span>
                        <span x-text="productUnit()" class="text-xs font-normal text-neutral-500"></span>
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-1 rounded-lg bg-neutral-100 p-1 text-center text-sm font-medium">
                    @foreach ($typeLabels as $value => $label)
                        <label class="cursor-pointer rounded-md py-1.5 transition" :class="form.type === '{{ $value }}' ? 'bg-white shadow-sm text-neutral-900 font-semibold' : 'text-neutral-500 hover:text-neutral-900'">
                            <input type="radio" name="type" value="{{ $value }}" x-model="form.type" class="sr-only"> {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div x-show="form.type === 'adjustment'" class="flex gap-4 text-xs font-medium text-neutral-600 bg-neutral-50 p-2.5 rounded-lg border border-line">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="adjustment_mode" value="count" x-model="form.adjustment_mode" class="text-neutral-900 focus:ring-neutral-900">
                        <span>Physical count (set exact stock)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="adjustment_mode" value="delta" x-model="form.adjustment_mode" class="text-neutral-900 focus:ring-neutral-900">
                        <span>Relative change (+/- delta)</span>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="stock-qty" class="block text-sm font-medium text-neutral-900 mb-1.5">
                            <span x-show="form.type === 'stock_in'">Quantity to add (+)</span>
                            <span x-show="form.type === 'stock_out'">Quantity to remove (-)</span>
                            <span x-show="form.type === 'adjustment' && form.adjustment_mode === 'count'">Counted physical stock</span>
                            <span x-show="form.type === 'adjustment' && form.adjustment_mode === 'delta'">Adjustment offset (+/-)</span>
                        </label>
                        <input id="stock-qty" name="quantity" type="number" step="any" min="0" x-model.number="form.quantity" required
                               class="h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                    </div>

                    <div x-show="form.type === 'stock_in'">
                        <x-form.select label="Reason" name="reason" :options="['Supplier delivery' => 'Supplier delivery', 'Customer return' => 'Customer return', 'Count correction' => 'Stock count correction', 'Other' => 'Other']" x-model="form.reason" />
                    </div>
                    <div x-show="form.type === 'stock_out'">
                        <x-form.select label="Reason" name="reason" :options="['Damaged' => 'Damaged goods', 'Expired' => 'Expired goods', 'Internal use' => 'Internal use / shrinkage', 'Other' => 'Other']" x-model="form.reason" />
                    </div>
                    <div x-show="form.type === 'adjustment'">
                        <x-form.select label="Reason" name="reason" :options="['Stock count correction' => 'Stock count correction', 'Damaged' => 'Damaged inventory', 'Expired' => 'Expired items found', 'Internal audit' => 'Internal audit correction']" x-model="form.reason" />
                    </div>
                </div>

                <div x-show="form.product_id" class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50/50 p-3 text-xs flex justify-between items-center text-neutral-700">
                    <span>Stock change: <strong class="font-mono text-sm" x-text="calculatedDiff()"></strong></span>
                    <span>Resulting balance: <strong class="font-mono text-sm text-neutral-900" x-text="calculatedBalance() + ' ' + productUnit()"></strong></span>
                </div>

                <x-form.textarea label="Note / Reference (optional)" name="note" rows="2" placeholder="e.g. PO #10023, shelf verification, invoice ref" x-model="form.note" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'stock-adjust')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="stock-form">Save stock update</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
