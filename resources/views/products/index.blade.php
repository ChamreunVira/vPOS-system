<x-layouts.app title="Products">
    <x-ui.page-header title="Products" subtitle="Everything you sell, with prices and stock levels.">
        <x-ui.button variant="secondary" icon="upload">Import</x-ui.button>
        <x-ui.button :href="route('products.create')" icon="plus">Add product</x-ui.button>
    </x-ui.page-header>

    <x-ui.card :padded="false">
        <div class="flex flex-wrap items-center gap-3 p-4">
            <x-ui.search class="min-w-56 flex-1" placeholder="Search by name, SKU or barcode" />
            <x-form.select name="category" class="w-48" placeholder="All categories" :options="$categories->pluck('name')->all()" />
            <x-form.select name="status" class="w-36" placeholder="All status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
        </div>
        <x-ui.table>
            <x-slot:head>
                <tr><th>Product</th><th>SKU</th><th>Category</th><th class="text-right">Cost</th><th class="text-right">Price</th><th class="text-right">Stock</th><th>Status</th><th></th></tr>
            </x-slot:head>
            @foreach ($products as $product)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $product['id']) }}" class="flex items-center gap-3 font-medium hover:underline">
                            <x-ui.product-thumb :product="$product" size="sm" /> {{ $product['name'] }}
                        </a>
                    </td>
                    <td class="font-money text-neutral-600">{{ $product['sku'] }}</td>
                    <td>{{ $product['category'] }}</td>
                    <td class="text-right text-neutral-600"><x-ui.money :amount="$product['cost']" /></td>
                    <td class="text-right"><x-ui.money :amount="$product['price']" /></td>
                    <td class="text-right font-money">{{ $product['stock'] }}</td>
                    <td><x-ui.status :status="\App\Support\MockData::stockLevel($product)" /></td>
                    <td class="text-right">
                        <div class="flex justify-end gap-1">
                            <x-ui.button :href="route('products.edit', $product['id'])" variant="ghost" size="sm" icon="pencil" aria-label="Edit" />
                            <form method="POST" action="{{ route('products.destroy', $product['id']) }}" onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" aria-label="Delete" />
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
        <x-ui.pagination :to="$products->count()" :total="$products->count()" />
    </x-ui.card>
</x-layouts.app>
