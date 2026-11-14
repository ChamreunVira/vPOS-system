@php $isEdit = (bool) $product; @endphp
<x-layouts.app :title="$isEdit ? 'Edit product' : 'New product'">
    <form method="POST" action="{{ $isEdit ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data"
          x-data="{
              cost: @js((float) old('cost', $product?->cost ?? 0)),
              price: @js((float) old('price', $product?->price ?? 0)),
              barcode: @js(old('barcode', $product?->barcode ?? '')),
              scannerOpen: false,
              scannerMessage: '',
              videoStream: null,
              detector: null,
              scanFrame: null,
              get margin() { return this.price > 0 ? Math.round((this.price - this.cost) / this.price * 100) : 0 },
              async startBarcodeScanner() {
                  this.scannerMessage = '';
                  if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                      this.scannerMessage = 'Camera scanning is not supported in this browser. Use a handheld scanner or enter the barcode.';
                      return;
                  }
                  try {
                      const supportedFormats = await BarcodeDetector.getSupportedFormats();
                      const formats = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'qr_code'].filter(format => supportedFormats.includes(format));
                      this.detector = new BarcodeDetector({ formats });
                      this.videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
                      this.$refs.barcodeVideo.srcObject = this.videoStream;
                      await this.$refs.barcodeVideo.play();
                      this.scannerOpen = true;
                      this.scanBarcode();
                  } catch (error) {
                      this.stopBarcodeScanner();
                      this.scannerMessage = 'Could not open the camera. Check camera permission and try again.';
                  }
              },
              async scanBarcode() {
                  if (!this.scannerOpen || !this.detector) return;
                  try {
                      const matches = await this.detector.detect(this.$refs.barcodeVideo);
                      if (matches.length) {
                          this.barcode = matches[0].rawValue;
                          this.stopBarcodeScanner();
                          return;
                      }
                  } catch (error) {
                      this.scannerMessage = 'Could not read that barcode. Try again or enter it manually.';
                  }
                  this.scanFrame = requestAnimationFrame(() => this.scanBarcode());
              },
              stopBarcodeScanner() {
                  this.scannerOpen = false;
                  if (this.scanFrame) cancelAnimationFrame(this.scanFrame);
                  this.scanFrame = null;
                  this.videoStream?.getTracks().forEach(track => track.stop());
                  this.videoStream = null;
              },
              destroy() { this.stopBarcodeScanner() },
          }">
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
                        <x-form.input class="sm:col-span-2" label="Product name" name="name" placeholder="e.g. Angkor Beer 330ml Can" :value="$product?->name" required />
                        <x-form.input label="SKU" name="sku" placeholder="BEV-001" :value="$product?->sku" required />
                        <div class="space-y-2">
                            <div class="flex items-end gap-2">
                                <x-form.input class="min-w-0 flex-1" label="Barcode" name="barcode" placeholder="Scan or type the barcode" x-model="barcode" @keydown.enter.prevent :value="$product?->barcode" />
                                <x-ui.button type="button" variant="secondary" class="mb-0.5 shrink-0" @click="startBarcodeScanner()">Scan</x-ui.button>
                            </div>
                            <p class="text-xs text-ink-muted">A handheld barcode scanner can type directly into this field.</p>
                            <div x-show="scannerOpen" x-cloak class="space-y-2 rounded-xl border border-line bg-neutral-950 p-3">
                                <video x-ref="barcodeVideo" class="max-h-64 w-full rounded-lg object-cover" playsinline muted></video>
                                <x-ui.button type="button" variant="secondary" @click="stopBarcodeScanner()">Stop camera</x-ui.button>
                            </div>
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
    </form>
</x-layouts.app>
