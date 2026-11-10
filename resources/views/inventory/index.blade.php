<x-layouts.app title="Inventory">
    @php
        $outOfStock = $products->where('stock', '<=', 0)->count();
        $typeLabels = ['stock_in' => 'Stock in', 'stock_out' => 'Stock out', 'adjustment' => 'Adjustment'];
    @endphp

    {{-- Shared Alpine scope: row buttons preselect a product and an action, then open the modal. --}}
    <div x-data="{
        form: { product_id: '', type: 'stock_in', quantity: 1 },
        open(productId = '', type = 'stock_in') { this.form = { product_id: productId, type: type, quantity: 1 }; $dispatch('open-modal', 'stock-adjust') },
    }">
        <x-ui.page-header title="Inventory" subtitle="Know what is on the shelf and what to reorder.">
            <x-ui.button variant="secondary" icon="download">Export stock report</x-ui.button>
            <x-ui.button icon="plus" @click="open()">Update stock</x-ui.button>
        </x-ui.page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Products" icon="package">{{ $products->count() }}</x-ui.stat-card>
            <x-ui.stat-card label="Stock value (at cost)" icon="wallet"><x-ui.money :amount="$stockValue" /></x-ui.stat-card>
            <x-ui.stat-card label="Low stock" icon="alert">{{ $lowStockProducts->count() - $outOfStock }}</x-ui.stat-card>
            <x-ui.stat-card label="Out of stock" icon="x">{{ $outOfStock }}</x-ui.stat-card>
        </div>

        @if ($lowStockProducts->isNotEmpty())
            <div class="mt-6 flex items-center gap-3 rounded-xl border border-neutral-950 bg-white p-4 text-sm">
                <x-ui.icon name="alert" class="size-5" />
                <p class="flex-1"><span class="font-medium">{{ $lowStockProducts->count() }} products need your attention.</span> Reorder them before they run out.</p>
            </div>
        @endif

        <div class="mt-6">
            <x-ui.tabs :tabs="['overview' => 'Stock overview', 'movements' => 'Movement history']">
                <x-ui.tab-panel name="overview">
                    <x-ui.card :padded="false">
                        <div class="p-4"><x-ui.search class="max-w-sm" placeholder="Search by name or SKU" /></div>
                        <x-ui.table>
                            <x-slot:head><tr><th>Product</th><th>SKU</th><th class="text-right">In stock</th><th class="text-right">Minimum</th><th>Status</th><th></th></tr></x-slot:head>
                            @foreach ($products as $product)
                                <tr>
                                    <td><span class="flex items-center gap-3 font-medium"><x-ui.product-thumb :product="$product" size="sm" /> {{ $product['name'] }}</span></td>
                                    <td class="font-money text-neutral-600">{{ $product['sku'] }}</td>
                                    <td class="text-right font-money">{{ $product['stock'] }}</td>
                                    <td class="text-right font-money text-neutral-600">{{ $product['min_stock'] }}</td>
                                    <td><x-ui.status :status="\App\Support\MockData::stockLevel($product)" /></td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <x-ui.button variant="secondary" size="sm" icon="arrow-down-left" :data-id="$product['id']" @click="open($el.dataset.id, 'stock_in')">In</x-ui.button>
                                            <x-ui.button variant="secondary" size="sm" icon="arrow-up-right" :data-id="$product['id']" @click="open($el.dataset.id, 'stock_out')">Out</x-ui.button>
                                            <x-ui.button variant="ghost" size="sm" :data-id="$product['id']" @click="open($el.dataset.id, 'adjustment')">Adjust</x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </x-ui.table>
                    </x-ui.card>
                </x-ui.tab-panel>

                <x-ui.tab-panel name="movements">
                    <x-ui.card :padded="false">
                        <x-ui.table>
                            <x-slot:head><tr><th>Date</th><th>Product</th><th>Type</th><th>Reference</th><th>By</th><th class="text-right">Change</th><th class="text-right">Balance</th></tr></x-slot:head>
                            @foreach ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $movement['at']->format('M j, g:i A') }}</td>
                                    <td class="font-medium">{{ $movement['product']['name'] }}@if ($movement['note'])<p class="text-xs font-normal text-ink-muted">{{ $movement['note'] }}</p>@endif</td>
                                    <td><x-ui.status :status="$movement['type']" /></td>
                                    <td class="font-money text-neutral-600">{{ $movement['reference'] }}</td>
                                    <td>{{ $movement['user'] }}</td>
                                    <td class="text-right font-money">{{ $movement['quantity'] > 0 ? '+' : '' }}{{ $movement['quantity'] }}</td>
                                    <td class="text-right font-money">{{ $movement['balance'] }}</td>
                                </tr>
                            @endforeach
                        </x-ui.table>
                    </x-ui.card>
                </x-ui.tab-panel>
            </x-ui.tabs>
        </div>

        <x-ui.modal name="stock-adjust" title="Update stock" description="Record stock coming in, going out, or a correction after a count.">
            <form id="stock-form" method="POST" action="{{ route('inventory.adjust') }}" class="space-y-4">
                @csrf
                <x-form.select label="Product" name="product_id" placeholder="Choose a product" x-model="form.product_id" :options="$products->pluck('name', 'id')->all()" />
                <div class="grid grid-cols-3 gap-1 rounded-lg bg-neutral-100 p-1 text-center text-sm font-medium">
                    @foreach ($typeLabels as $value => $label)
                        <label class="cursor-pointer rounded-md py-1.5" :class="form.type === '{{ $value }}' ? 'bg-white shadow-sm' : 'text-neutral-500'">
                            <input type="radio" name="type" value="{{ $value }}" x-model="form.type" class="sr-only"> {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input label="Quantity" name="quantity" type="number" min="1" x-model.number="form.quantity" />
                    <x-form.select label="Reason" name="reason" :options="['Supplier delivery', 'Customer return', 'Damaged', 'Expired', 'Stock count correction']" />
                </div>
                <x-form.textarea label="Note (optional)" name="note" rows="2" placeholder="Invoice or purchase order number" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'stock-adjust')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="stock-form">Save stock update</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
