<x-layouts.app :title="$product['name']">
    <x-ui.page-header :title="$product['name']" :subtitle="$product['category'].' · '.$product['sku']" :back="route('products.index')">
        <x-ui.button :href="route('products.edit', $product['id'])" icon="pencil">Edit product</x-ui.button>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card><x-ui.product-thumb :product="$product" size="lg" /></x-ui.card>

        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.stat-card label="Selling price"><x-ui.money :amount="$product['price']" /></x-ui.stat-card>
                <x-ui.stat-card label="Cost price"><x-ui.money :amount="$product['cost']" /></x-ui.stat-card>
                <x-ui.stat-card label="In stock" :hint="'Minimum level '.$product['min_stock']">{{ $product['stock'] }}</x-ui.stat-card>
            </div>

            <x-ui.card title="Details">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">SKU</dt><dd class="font-money">{{ $product['sku'] }}</dd></div>
                    <div><dt class="text-ink-muted">Barcode</dt><dd class="font-money">{{ $product['barcode'] }}</dd></div>
                    <div><dt class="text-ink-muted">Category</dt><dd>{{ $product['category'] }}</dd></div>
                    <div><dt class="text-ink-muted">Stock status</dt><dd><x-ui.status :status="\App\Support\MockData::stockLevel($product)" /></dd></div>
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Description</dt><dd>{{ $product['description'] }}</dd></div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Stock history" :padded="false">
                @if ($movements->isEmpty())
                    <x-ui.empty-state icon="layers" title="No movements yet" description="Stock in, stock out and sales will appear here." />
                @else
                    <x-ui.table>
                        <x-slot:head><tr><th>Date</th><th>Type</th><th>Reference</th><th class="text-right">Change</th><th class="text-right">Balance</th></tr></x-slot:head>
                        @foreach ($movements as $movement)
                            <tr>
                                <td>{{ $movement['at']->format('M j, g:i A') }}</td>
                                <td><x-ui.status :status="$movement['type']" /></td>
                                <td class="font-money text-neutral-600">{{ $movement['reference'] }}</td>
                                <td class="text-right font-money">{{ $movement['quantity'] > 0 ? '+' : '' }}{{ $movement['quantity'] }}</td>
                                <td class="text-right font-money">{{ $movement['balance'] }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
