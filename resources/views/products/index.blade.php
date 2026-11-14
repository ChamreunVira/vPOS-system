<x-layouts.app title="Products">
    <x-ui.page-header title="Products" subtitle="Everything you sell, with prices and stock levels.">
        <x-ui.button :href="route('products.create')" icon="plus">Add product</x-ui.button>
    </x-ui.page-header>

    <x-ui.card :padded="false">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
            <x-ui.search class="min-w-56 flex-1" placeholder="Search by name, SKU or barcode" />
            <x-form.select label="Category" name="category_id" class="w-48" placeholder="All categories" :options="$categories" :value="request('category_id')" />
            <x-form.select label="Status" name="status" class="w-36" placeholder="All status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" />
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            @if (request()->hasAny(['q', 'category_id', 'status']))
                <x-ui.button :href="route('products.index')" variant="ghost">Clear</x-ui.button>
            @endif
        </form>

        <x-ui.table>
            <x-slot:head>
                <tr><th>Product</th><th>SKU</th><th>Category</th><th>Unit</th><th class="text-right">Cost</th><th class="text-right">Price</th><th class="text-right">Stock</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr>
            </x-slot:head>
            @forelse ($products as $product)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 font-medium hover:underline">
                            <x-ui.product-thumb :product="$product" size="sm" /> {{ $product->name }}
                        </a>
                        @if ($product->barcode)<span class="ml-12 text-xs text-ink-muted">Barcode: {{ $product->barcode }}</span>@endif
                    </td>
                    <td class="font-money text-neutral-600">{{ $product->sku }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>{{ $product->unit?->abbreviation ?? $product->unit?->name ?? '—' }}</td>
                    <td class="text-right text-neutral-600"><x-ui.money :amount="$product->cost" /></td>
                    <td class="text-right"><x-ui.money :amount="$product->price" /></td>
                    <td class="text-right font-money">{{ $product->stock }}</td>
                    <td><x-ui.status :status="$product->status" /></td>
                    <td class="text-right">
                        <div class="flex justify-end gap-1">
                            <x-ui.button :href="route('products.edit', $product)" variant="ghost" size="sm" icon="pencil" aria-label="Edit product" />
                            @if ($product->status === 'active')
                                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Deactivate this product? It will remain in sales history.')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="pause" aria-label="Deactivate product" />
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-5 py-12 text-center"><x-ui.empty-state icon="package" title="No products found" description="Try changing the filters or add a product to your catalogue." /></td></tr>
            @endforelse
        </x-ui.table>
        <div class="border-t border-line px-5 py-3">{{ $products->links() }}</div>
    </x-ui.card>
</x-layouts.app>
