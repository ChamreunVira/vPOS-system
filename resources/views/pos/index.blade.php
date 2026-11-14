<x-layouts.pos>
    <div class="flex h-full flex-col bg-canvas lg:flex-row"
         x-data="posTerminal({
             products: @js($products),
             config: {
                 vatRate: {{ config('vpos.vat_rate') }},
                 exchangeRate: {{ config('vpos.exchange_rate') }},
                 nextInvoice: {{ $nextInvoice }},
                 customers: @js($customers),
                 checkoutUrl: '{{ route('pos.checkout') }}',
             },
         })"
         @keydown.f2.window.prevent="$refs.barcode.focus()">

        {{-- ============ Left: catalogue ============ --}}
        <section class="flex min-h-0 flex-1 flex-col">
            <header class="flex flex-wrap items-center gap-3 border-b border-line bg-white px-4 py-3">
                <x-ui.button :href="route('dashboard')" variant="ghost" size="sm" icon="arrow-left">{{ __('Dashboard') }}</x-ui.button>
                <div class="leading-tight">
                    <p class="text-sm font-semibold">{{ config('vpos.store') }}</p>
                    <p class="text-xs text-ink-muted">{{ config('vpos.register') }} · Sreymao Lim</p>
                </div>
                <div class="relative ml-auto w-full sm:w-72">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
                    <input type="search" x-model="query" placeholder="{{ __('Search products') }}" class="h-10 w-full rounded-lg border border-neutral-300 pl-9 pr-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                </div>
                <form @submit.prevent="scanBarcode()" class="relative w-full sm:w-64">
                    <x-ui.icon name="scan" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
                    <input x-ref="barcode" x-model="barcode" type="text" inputmode="numeric" autofocus placeholder="{{ __('Scan barcode (F2)') }}" class="font-money h-10 w-full rounded-lg border border-neutral-300 pl-9 pr-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                </form>
                <x-layout.language-switcher />
            </header>

            <div class="flex gap-2 overflow-x-auto border-b border-line bg-white px-4 py-2.5">
                <template x-for="name in categories" :key="name">
                    <button type="button" @click="category = name" x-text="name"
                            :class="category === name ? 'bg-neutral-950 text-white' : 'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'"
                            class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium"></button>
                </template>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <p x-show="notice" x-cloak x-text="notice" class="mb-3 rounded-lg bg-neutral-950 px-4 py-2 text-sm text-white"></p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button type="button" @click="addToCart(product)" :disabled="product.stock <= 0"
                                class="flex flex-col rounded-xl border border-line bg-white p-3 text-left transition hover:border-neutral-950 disabled:opacity-50 disabled:hover:border-line">
                            <span class="flex aspect-[4/3] items-center justify-center rounded-lg bg-neutral-100 text-5xl" x-text="product.emoji" aria-hidden="true"></span>
                            <span class="mt-3 line-clamp-2 min-h-10 text-sm font-medium" x-text="product.name"></span>
                            <span class="mt-2 flex items-center justify-between">
                                <span class="font-money text-sm font-semibold" x-text="usd(product.price)"></span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                      :class="product.stock <= 0 ? 'bg-neutral-950 text-white' : (product.stock <= product.min_stock ? 'border border-neutral-500' : 'bg-neutral-100')"
                                      x-text="product.stock <= 0 ? 'Out of stock' : (product.stock <= product.min_stock ? product.stock + ' left' : product.stock + ' in stock')"></span>
                            </span>
                        </button>
                    </template>
                </div>
                <div x-show="! filteredProducts.length" x-cloak class="py-16 text-center text-sm text-ink-muted">No products match your search.</div>
            </div>
        </section>

        {{-- ============ Right: cart ============ --}}
        <aside class="flex max-h-[55vh] min-h-0 w-full flex-col border-t border-line bg-white lg:max-h-none lg:w-[400px] lg:border-l lg:border-t-0">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-base font-semibold">Current sale <span class="ml-1 rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium" x-text="itemCount + ' items'"></span></h2>
                <div class="flex gap-1">
                    <x-ui.button variant="ghost" size="sm" icon="pause" @click="resumeSale()" x-show="heldSales.length" x-cloak><span x-text="'Held · ' + heldSales.length"></span></x-ui.button>
                </div>
            </div>

            <div class="border-b border-line px-5 py-3">
                <label class="sr-only" for="pos-customer">Customer</label>
                <select id="pos-customer" x-model="customerId" class="h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                    <option value="">Walk-in customer</option>
                    <template x-for="customer in config.customers" :key="customer.id"><option :value="customer.id" x-text="customer.name"></option></template>
                </select>
            </div>

            <ul class="min-h-0 flex-1 divide-y divide-neutral-100 overflow-y-auto px-5">
                <template x-for="line in cart" :key="line.id">
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-neutral-100 text-2xl" x-text="line.emoji" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium" x-text="line.name"></p>
                            <p class="font-money text-xs text-ink-muted" x-text="usd(line.price) + ' each'"></p>
                        </div>
                        <div class="flex items-center rounded-lg border border-neutral-300">
                            <button type="button" @click="decrease(line)" class="p-1.5 hover:bg-neutral-100" aria-label="Decrease quantity"><x-ui.icon name="minus" /></button>
                            <span class="font-money w-7 text-center text-sm" x-text="line.quantity"></span>
                            <button type="button" @click="increase(line)" class="p-1.5 hover:bg-neutral-100" aria-label="Increase quantity"><x-ui.icon name="plus" /></button>
                        </div>
                        <span class="font-money w-16 text-right text-sm font-medium" x-text="usd(line.price * line.quantity)"></span>
                        <button type="button" @click="removeFromCart(line)" class="rounded-lg p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-950" aria-label="Remove item"><x-ui.icon name="x" /></button>
                    </li>
                </template>
                <li x-show="! cart.length" x-cloak><x-ui.empty-state icon="cart" title="Cart is empty" description="Tap a product or scan a barcode to start the sale." /></li>
            </ul>

            <div class="space-y-3 border-t border-line px-5 py-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-ink-muted">Discount</span>
                    <div class="flex gap-1">
                        @foreach (config('vpos.discount_options') as $percent)
                            <button type="button" @click="discountPercent = {{ $percent }}"
                                    :class="discountPercent === {{ $percent }} ? 'bg-neutral-950 text-white' : 'border border-neutral-300 hover:bg-neutral-50'"
                                    class="rounded-md px-2.5 py-1 text-xs font-medium">{{ $percent }}%</button>
                        @endforeach
                    </div>
                </div>
                <dl class="space-y-1.5">
                    <div class="flex justify-between"><dt class="text-ink-muted">Subtotal</dt><dd class="font-money" x-text="usd(subtotal)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted" x-text="'Discount · ' + discountPercent + '%'"></dt><dd class="font-money" x-text="'−' + usd(discountAmount)"></dd></div>
                    <div class="flex justify-between"><dt class="text-ink-muted">VAT · {{ config('vpos.vat_rate') }}%</dt><dd class="font-money" x-text="usd(vatAmount)"></dd></div>
                </dl>
                <div class="flex items-end justify-between border-t border-line pt-3">
                    <span class="text-base font-semibold">Total</span>
                    <span class="text-right"><span class="font-money block text-2xl font-semibold" x-text="usd(total)"></span><span class="font-money text-xs text-ink-muted" x-text="khr(total)"></span></span>
                </div>
                <x-ui.button size="lg" class="w-full" icon="wallet" @click="openPayment()" x-bind:disabled="! cart.length"><span x-text="'Charge ' + usd(total)"></span></x-ui.button>
                <div class="grid grid-cols-2 gap-2">
                    <x-ui.button variant="secondary" @click="holdSale()" x-bind:disabled="! cart.length">Hold sale</x-ui.button>
                    <x-ui.button variant="secondary" @click="clearCart()" x-bind:disabled="! cart.length">Clear</x-ui.button>
                </div>
            </div>
        </aside>

        {{-- ============ Payment modal ============ --}}
        <div x-show="paymentOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="paymentOpen = false">
            <div class="absolute inset-0 bg-neutral-950/40" @click="paymentOpen = false"></div>
            <div class="relative max-h-full w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <header class="flex items-start justify-between border-b border-line px-6 py-4">
                    <div><h2 class="text-base font-semibold">Collect payment</h2><p class="text-sm text-ink-muted"><span x-text="itemCount"></span> items</p></div>
                    <button type="button" @click="paymentOpen = false" class="rounded-lg p-1 hover:bg-neutral-100" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
                </header>
                <div class="grid gap-6 p-6 md:grid-cols-5">
                    <div class="space-y-4 md:col-span-3">
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['cash' => ['Cash', 'banknote'], 'khqr' => ['KHQR', 'qr-code'], 'card' => ['Card', 'credit-card']] as $key => [$label, $icon])
                                <button type="button" @click="method = '{{ $key }}'"
                                        :class="method === '{{ $key }}' ? 'border-neutral-950 bg-neutral-50 ring-1 ring-neutral-950' : 'border-neutral-300 hover:bg-neutral-50'"
                                        class="flex flex-col items-center gap-1.5 rounded-xl border p-3 text-sm font-medium"><x-ui.icon :name="$icon" class="size-5" /> {{ $label }}</button>
                            @endforeach
                        </div>

                        <div x-show="method === 'cash'" class="space-y-3">
                            <div class="rounded-lg border border-neutral-300 px-4 py-3"><p class="text-xs text-ink-muted">Cash received</p><p class="font-money text-2xl" x-text="received ? '$' + received : '$0.00'"></p></div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="amount in quickAmounts" :key="amount">
                                    <button type="button" @click="received = String(Number(amount.toFixed(2)))" class="font-money rounded-lg border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50" x-text="amount === total ? 'Exact ' + usd(amount) : usd(amount)"></button>
                                </template>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', 'back'] as $key)
                                    <button type="button" @click="pressKey('{{ $key }}')" class="font-money flex h-12 items-center justify-center rounded-lg bg-neutral-100 text-lg hover:bg-neutral-200">
                                        @if ($key === 'back') <x-ui.icon name="chevron-left" class="size-5" /> @else {{ $key }} @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="method === 'khqr'" x-cloak class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-400 p-8 text-center">
                            <x-ui.icon name="qr-code" class="size-32 text-neutral-950" />
                            <p class="text-sm font-medium">Ask the customer to scan with any Bakong banking app.</p>
                            <p class="text-xs text-ink-muted">Replace this block with the KHQR image generated by your backend.</p>
                        </div>

                        <div x-show="method === 'card'" x-cloak class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-400 p-8 text-center">
                            <x-ui.icon name="credit-card" class="size-12" />
                            <p class="text-sm font-medium">Tap, insert or swipe the card on the terminal.</p>
                        </div>
                    </div>

                    <div class="space-y-4 md:col-span-2">
                        <div class="rounded-xl bg-neutral-100 p-4">
                            <p class="text-sm text-ink-muted">Amount due</p>
                            <p class="font-money text-3xl font-semibold" x-text="usd(total)"></p>
                            <p class="font-money text-sm text-ink-muted" x-text="khr(total)"></p>
                        </div>
                        <div x-show="method === 'cash'" class="rounded-xl border border-neutral-300 p-4">
                            <p class="text-sm text-ink-muted">Change to give</p>
                            <p class="font-money text-3xl font-semibold" x-text="usd(change)"></p>
                            <p class="font-money text-sm text-ink-muted" x-text="khr(change)"></p>
                            <p class="mt-2 text-xs text-ink-muted" x-show="receivedAmount > 0 && receivedAmount < total" x-cloak>Customer still owes <span x-text="usd(total - receivedAmount)"></span>.</p>
                        </div>
                        <x-ui.button size="lg" class="w-full" icon="check" @click="completeSale()" x-bind:disabled="! canComplete">Complete sale</x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Receipt modal ============ --}}
        <div x-show="receiptOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-neutral-950/40"></div>
            <div class="relative flex max-h-full w-full max-w-md flex-col rounded-xl bg-white shadow-xl">
                <header class="border-b border-line px-6 py-4 text-center">
                    <span class="mx-auto flex size-10 items-center justify-center rounded-full bg-neutral-950 text-white"><x-ui.icon name="check" class="size-5" /></span>
                    <h2 class="mt-2 text-base font-semibold">Payment received</h2>
                    <p class="text-sm text-ink-muted" x-text="lastSale && lastSale.number"></p>
                </header>
                <div class="overflow-y-auto bg-neutral-50 p-6">
                    <div id="receipt" class="mx-auto w-full max-w-[300px] bg-white p-5 text-xs shadow-sm" x-show="lastSale">
                        <template x-if="lastSale">
                            <div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold">{{ config('vpos.store') }}</p>
                                    <p class="text-ink-muted" x-text="lastSale.placedAt"></p>
                                    <p class="text-ink-muted" x-text="lastSale.customer"></p>
                                </div>
                                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                                <template x-for="line in lastSale.lines" :key="line.id">
                                    <div class="mb-1.5 flex justify-between gap-2"><span x-text="line.quantity + ' × ' + line.name"></span><span class="font-money" x-text="usd(line.price * line.quantity)"></span></div>
                                </template>
                                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                                <div class="flex justify-between"><span>Subtotal</span><span class="font-money" x-text="usd(lastSale.subtotal)"></span></div>
                                <div class="flex justify-between" x-show="lastSale.discountPercent > 0"><span x-text="'Discount ' + lastSale.discountPercent + '%'"></span><span class="font-money" x-text="'−' + usd(lastSale.discountAmount)"></span></div>
                                <div class="flex justify-between"><span>VAT {{ config('vpos.vat_rate') }}%</span><span class="font-money" x-text="usd(lastSale.vatAmount)"></span></div>
                                <div class="mt-2 flex justify-between text-sm font-semibold"><span>Total</span><span class="font-money" x-text="usd(lastSale.total)"></span></div>
                                <div class="flex justify-between text-ink-muted"><span></span><span class="font-money" x-text="khr(lastSale.total)"></span></div>
                                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                                <div class="flex justify-between"><span x-text="'Paid by ' + lastSale.method.toUpperCase()"></span><span class="font-money" x-text="usd(lastSale.received)"></span></div>
                                <div class="flex justify-between"><span>Change</span><span class="font-money" x-text="usd(lastSale.change)"></span></div>
                                <p class="mt-4 text-center text-ink-muted">Thank you. Come again!</p>
                            </div>
                        </template>
                    </div>
                </div>
                <footer class="grid grid-cols-2 gap-2 border-t border-line p-4">
                    <x-ui.button variant="secondary" icon="printer" @click="window.print()">Print receipt</x-ui.button>
                    <x-ui.button icon="plus" @click="startNewSale()">New sale</x-ui.button>
                </footer>
            </div>
        </div>
    </div>
</x-layouts.pos>
