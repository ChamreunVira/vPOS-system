@php $isEdit = (bool) $product; @endphp
<x-layouts.app :title="$isEdit ? 'Edit product' : 'New product'">
    <form method="POST" action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data"
          @image-selected.window="recognizeProductImage($event.detail.file)"
          x-data="productForm({
              name: @js(old('name', $product?->name ?? '')),
              userModifiedName: {{ old('name') || ($product && $product->name) ? 'true' : 'false' }},
              cost: @js((float) old('cost', $product?->cost ?? 0)),
              price: @js((float) old('price', $product?->price ?? 0)),
              barcode: @js(old('barcode', $product?->barcode ?? '')),
              recognizeUrl: '{{ route('products.recognize-image') }}',
          })">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.page-header :title="$isEdit ? 'Edit product' : 'New product'" :subtitle="$isEdit ? $product->name : 'Add an item to your catalogue.'" :back="route('products.index')">
            <x-ui.button :href="route('products.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">Save product</x-ui.button>
        </x-ui.page-header>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Product details">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2 space-y-2">
                            <x-form.input label="Product name" name="name" placeholder="e.g. Angkor Beer 330ml Can" x-model="name" @input="userModifiedName = true" :value="$product?->name" required />

                            {{-- OCR Loading Indicator --}}
                            <div x-show="ocrLoading" x-cloak class="flex items-center gap-2 rounded-lg bg-neutral-100 px-3 py-2 text-xs text-neutral-800 animate-pulse">
                                <svg class="size-3.5 animate-spin text-neutral-900" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span class="font-medium text-neutral-900">Recognizing product text from image…</span>
                            </div>

                            {{-- OCR Suggested Name Banner (shown if user had already entered a name) --}}
                            <div x-show="ocrSuggestedName && ocrSuggestedName !== name && !ocrLoading" x-cloak class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800">
                                <div class="flex items-center gap-1.5">
                                    <x-ui.icon name="sparkles" class="size-4 shrink-0 text-emerald-600" />
                                    <span>Suggested: <strong x-text="ocrSuggestedName"></strong></span>
                                </div>
                                <button type="button" @click="name = ocrSuggestedName; userModifiedName = true" class="font-semibold underline hover:text-emerald-950">
                                    Use this name
                                </button>
                            </div>

                            {{-- Detected alternative text candidate pills --}}
                            <div x-show="ocrCandidates.length > 1 && !ocrLoading" x-cloak class="space-y-1">
                                <span class="text-xs text-ink-muted">Detected text (click to use):</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="candidate in ocrCandidates" :key="candidate">
                                        <button type="button" @click="name = candidate; userModifiedName = true"
                                                :class="name === candidate ? 'bg-neutral-950 text-white' : 'border border-neutral-200 bg-neutral-100 hover:bg-neutral-200 text-neutral-800'"
                                                class="rounded-md px-2 py-0.5 text-xs font-medium transition-colors"
                                                x-text="candidate"></button>
                                    </template>
                                </div>
                            </div>

                            {{-- OCR Status message --}}
                            <p x-show="ocrMessage && !ocrLoading && (!ocrSuggestedName || ocrSuggestedName === name)" x-cloak class="text-xs text-ink-muted" x-text="ocrMessage"></p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-end gap-2">
                                <x-form.input class="min-w-0 flex-1" label="Barcode" name="barcode" placeholder="Scan or type the barcode" x-model="barcode" @keydown.enter.prevent :value="$product?->barcode" />
                                <x-ui.button type="button" variant="secondary" icon="scan" class="mb-0.5 shrink-0" @click="startBarcodeScanner()">Scan</x-ui.button>
                            </div>
                            <p class="text-xs text-ink-muted">A handheld barcode scanner or your device camera can fill this field.</p>
                            <p x-show="scannedFeedback" x-cloak class="text-xs font-semibold text-emerald-700" x-text="scannedFeedback"></p>
                            <p x-show="scannerMessage" x-cloak role="status" class="text-xs text-amber-800" x-text="scannerMessage"></p>
                        </div>
                        <x-form.select label="Category" name="category_id" placeholder="Choose a category" :options="$categories" :value="$product?->category_id" required />
                        <x-form.select label="Unit" name="unit_id" placeholder="Choose a unit" :options="$units" :value="$product?->unit_id" required />
                    </div>
                </x-ui.card>

                <x-ui.card title="Pricing" description="Margin updates as you type.">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-form.input label="Cost price" name="cost" type="number" step="0.01" min="0" prefix="$" x-model.number="cost" :value="$product?->cost" required />
                        <x-form.input label="Selling price" name="price" type="number" step="0.01" min="0" prefix="$" x-model.number="price" :value="$product?->price" required />
                        <div class="space-y-1.5">
                            <span class="block text-sm font-medium">Margin</span>
                            <p class="flex h-10 items-center rounded-lg bg-neutral-100 px-3 font-money text-sm" x-text="margin + '%'" aria-live="polite"></p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Stock">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input label="Stock quantity" name="stock" type="number" step="any" min="0" :value="$product?->stock ?? 0" required />
                        <x-form.input label="Minimum stock level" name="min_stock" type="number" step="any" min="0" hint="You will be alerted at or below this number." :value="$product?->min_stock ?? 10" required />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Product image">
                    <x-form.image-upload label="Product image" name="image" :current="$product?->image ? (str_starts_with($product->image, 'http') ? $product->image : \Illuminate\Support\Facades\Storage::disk('public')->url($product->image)) : null" />
                    @error('image')<p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                </x-ui.card>
                <x-ui.card title="Availability">
                    <x-form.toggle name="status" label="Active" hint="Inactive products stay in sales history and are hidden from the POS." :checked="($product?->status ?? 'active') === 'active'" />
                </x-ui.card>
            </div>
        </div>

        {{-- ============ Camera barcode scanner modal ============ --}}
        <div x-show="scannerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="product-scanner-title" @keydown.escape.window="stopBarcodeScanner()">
            <button type="button" class="absolute inset-0 bg-neutral-950/50" aria-label="Close camera scanner" @click="stopBarcodeScanner()"></button>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <header class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="product-scanner-title" class="font-semibold text-neutral-950">Scan product barcode</h2>
                        <p class="mt-1 text-sm text-ink-muted">Position the product barcode within the camera view.</p>
                    </div>
                    <button type="button" @click="stopBarcodeScanner()" class="rounded-lg p-1 hover:bg-neutral-100" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
                </header>

                <div class="relative overflow-hidden rounded-xl bg-neutral-950 min-h-[260px] flex items-center justify-center">
                    <div id="product-barcode-reader" class="w-full"></div>
                    <div x-show="scannerLoading" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-neutral-950/80 text-white">
                        <x-ui.icon name="scan" class="size-8 animate-pulse text-neutral-300" />
                        <span class="text-sm font-medium text-neutral-300">Starting camera…</span>
                    </div>
                    {{-- Reticle / Aiming Guide with top-to-bottom laser animation --}}
                    <div x-show="!scannerLoading && !scannerMessage" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="relative h-32 w-64 rounded-xl border-2 border-emerald-400/90 shadow-[0_0_16px_rgba(52,211,153,0.35)] overflow-hidden">
                            <div class="scanner-laser"></div>
                        </div>
                    </div>
                </div>

                <p x-show="scannerMessage" x-cloak role="status" x-text="scannerMessage" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900"></p>

                <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3">
                    <div>
                        <input type="file" x-ref="barcodeFileInput" accept="image/*" class="hidden" @change="scanFromFile($event)">
                        <button type="button" class="text-xs text-neutral-500 hover:text-neutral-900 underline" @click="$refs.barcodeFileInput.click()">Upload barcode image</button>
                    </div>
                    <x-ui.button type="button" variant="secondary" size="sm" @click="stopBarcodeScanner()">Cancel</x-ui.button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.app>
