<x-layouts.app :title="$product->name">
    <x-ui.page-header :title="$product->name" :subtitle="($product->category?->name ?? 'Uncategorized').' · '.$product->sku" :back="route('products.index')">
        <x-ui.button :href="route('products.edit', $product)" icon="pencil">Edit product</x-ui.button>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card><x-ui.product-thumb :product="$product" size="lg" /></x-ui.card>

        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.stat-card label="Selling price"><x-ui.money :amount="$product->price" /></x-ui.stat-card>
                <x-ui.stat-card label="Cost price"><x-ui.money :amount="$product->cost" /></x-ui.stat-card>
                <x-ui.stat-card label="In stock" :hint="'Minimum level '.$product->min_stock">{{ $product->stock }} {{ $product->unit?->abbreviation }}</x-ui.stat-card>
            </div>

            <x-ui.card title="Details">
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">SKU</dt><dd class="font-money">{{ $product->sku }}</dd></div>
                    <div><dt class="text-ink-muted">Barcode</dt><dd class="font-money">{{ $product->barcode ?: '—' }}</dd></div>
                    <div><dt class="text-ink-muted">Category</dt><dd>{{ $product->category?->name ?? '—' }}</dd></div>
                    <div><dt class="text-ink-muted">Unit</dt><dd>{{ $product->unit?->name ?? '—' }}</dd></div>
                    <div><dt class="text-ink-muted">Minimum stock</dt><dd>{{ $product->min_stock }} {{ $product->unit?->abbreviation }}</dd></div>
                    <div><dt class="text-ink-muted">Availability</dt><dd><x-ui.status :status="$product->status" /></dd></div>
                </dl>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
