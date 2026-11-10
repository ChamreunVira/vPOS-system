@php $isEdit = (bool) $product; @endphp
<x-layouts.app :title="$isEdit ? 'Edit product' : 'New product'">
    <form method="POST" action="{{ $isEdit ? route('products.update', $product['id']) : route('products.store') }}" enctype="multipart/form-data"
          x-data="{ cost: {{ $product['cost'] ?? 0 }}, price: {{ $product['price'] ?? 0 }}, get margin() { return this.price > 0 ? Math.round((this.price - this.cost) / this.price * 100) : 0 } }">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.page-header :title="$isEdit ? 'Edit product' : 'New product'" :subtitle="$isEdit ? $product['name'] : 'Add an item to your catalogue.'" :back="route('products.index')">
            <x-ui.button :href="route('products.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">Save product</x-ui.button>
        </x-ui.page-header>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Product details">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input class="sm:col-span-2" label="Product name" name="name" placeholder="e.g. Angkor Beer 330ml Can" :value="$product['name'] ?? null" required />
                        <x-form.input label="SKU" name="sku" placeholder="BEV-001" :value="$product['sku'] ?? null" />
                        <x-form.input label="Barcode" name="barcode" placeholder="Scan or type the barcode" :value="$product['barcode'] ?? null" />
                        <x-form.select label="Category" name="category_id" placeholder="Choose a category" :options="$categories->pluck('name', 'id')->all()" :value="$product['category_id'] ?? null" />
                        <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$product['description'] ?? null" /></div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Pricing" description="Margin updates as you type.">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-form.input label="Cost price" name="cost" type="number" step="0.01" min="0" prefix="$" x-model.number="cost" :value="$product['cost'] ?? null" />
                        <x-form.input label="Selling price" name="price" type="number" step="0.01" min="0" prefix="$" x-model.number="price" :value="$product['price'] ?? null" />
                        <div class="space-y-1.5">
                            <span class="block text-sm font-medium">Margin</span>
                            <p class="flex h-10 items-center rounded-lg bg-neutral-100 px-3 font-money text-sm" x-text="margin + '%'"></p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Stock">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input label="Stock quantity" name="stock" type="number" min="0" :value="$product['stock'] ?? 0" />
                        <x-form.input label="Minimum stock level" name="min_stock" type="number" min="0" hint="You will be alerted at or below this number." :value="$product['min_stock'] ?? 10" />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Product image"><x-form.image-upload label="Product image" name="image" :current="$product['image'] ?? null" /></x-ui.card>
                <x-ui.card title="Availability">
                    <x-form.toggle name="status" label="Active" hint="Inactive products are hidden from the POS." :checked="($product['status'] ?? 'active') === 'active'" />
                </x-ui.card>
            </div>
        </div>
    </form>
</x-layouts.app>
