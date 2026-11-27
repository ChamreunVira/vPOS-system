<x-layouts.pos>
    <div x-data="posTerminal({
        products: @js($products),
        config: {
            vatRate: {{ config('vpos.vat_rate') }},
            exchangeRate: {{ config('vpos.exchange_rate') }},
            customers: @js($customers),
            checkoutUrl: '{{ route('pos.checkout') }}',
            scannerSessionUrl: '{{ route('pos.scanner.session') }}',
            scannerStatusUrl: '{{ route('pos.scanner.status') }}',
            scannerRegenerateUrl: '{{ route('pos.scanner.regenerate') }}',
            scannerDisconnectUrl: '{{ route('pos.scanner.disconnect') }}',
            scannerEventsUrl: '{{ route('pos.scanner.events') }}',
        },
    })" @keydown.window="handleShortcut($event)"
        class="flex h-full flex-col bg-canvas lg:flex-row [&_button:focus-visible]:outline-none [&_button:focus-visible]:ring-2 [&_button:focus-visible]:ring-neutral-950 [&_input:focus-visible]:outline-none [&_input:focus-visible]:ring-2 [&_input:focus-visible]:ring-neutral-950 [&_select:focus-visible]:outline-none [&_select:focus-visible]:ring-2 [&_select:focus-visible]:ring-neutral-950 motion-reduce:[&_button]:transition-none">

        <section class="flex min-h-0 min-w-0 flex-1 flex-col">
            <header
                class="flex flex-wrap items-center gap-2 border-b border-line bg-white px-3 py-2 sm:gap-3 sm:px-4 sm:py-3">
                <x-ui.button :href="route('dashboard')" variant="ghost" size="md" icon="arrow-left" :aria-label="__('Back to dashboard')"
                    :title="__('Back to dashboard')"></x-ui.button>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-semibold">{{ config('vpos.store') }}</p>
                    <p class="truncate text-xs text-ink-muted">{{ config('vpos.register') }} ·
                        {{ auth()->user()->name }}</p>
                </div>
                <div class="relative ml-auto w-full sm:w-64">
                    <x-ui.icon name="search"
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
                    <input x-ref="search" type="search" x-model="query" placeholder="{{ __('Search products') }}"
                        aria-label="{{ __('Search products') }}"
                        class="h-10 w-full rounded-lg border border-neutral-300 pl-9 pr-10 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                    <button type="button" x-show="query" x-cloak @click="query = ''; $refs.search.focus()"
                        class="absolute right-1 top-1 inline-flex size-8 items-center justify-center rounded-md text-neutral-500 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        aria-label="{{ __('Clear product search') }}" title="{{ __('Clear search') }}">
                        <x-ui.icon name="x" />
                    </button>
                </div>
                <form @submit.prevent="scanBarcode()" class="relative w-full sm:w-64">
                    <x-ui.icon name="scan"
                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
                    <input x-ref="barcode" x-model="barcode" type="text" inputmode="numeric" autofocus
                        aria-label="{{ __('Scan product barcode') }}" placeholder="{{ __('Scan barcode (F2)') }}"
                        class="font-money h-10 w-full rounded-lg border border-neutral-300 pl-9 pr-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                </form>
                <x-ui.button type="button" variant="secondary" icon="camera" @click="startBarcodeScanner()">
                    {{ __('Camera') }}
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" @click="openMobileScannerModal()" class="relative">
                    <span class="flex items-center gap-1.5">
                        <x-ui.icon name="smartphone" class="size-4" />
                        <span>{{ __('Mobile scanner') }}</span>
                        <span x-show="isMobileConnected" x-cloak
                            class="size-2 rounded-full bg-emerald-500 animate-pulse" title="Mobile connected"></span>
                    </span>
                </x-ui.button>
            </header>

            {{-- <div
                class="flex flex-wrap items-center gap-2 border-b border-line bg-neutral-50 px-4 py-2 text-xs text-neutral-700">
                <span class="font-medium">{{ __('Shortcuts') }}:</span>
                <span><kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">F2</kbd>
                    {{ __('Scan') }}</span>
                <span><kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">F4</kbd>
                    {{ __('Cash') }}</span>
                <span><kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">F8</kbd>
                    {{ __('KHQR') }}</span>
                <span><kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">F9</kbd>
                    {{ __('Card') }}</span>
                <span><kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">F10</kbd>
                    {{ __('Complete sale') }}</span>
                <span><kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">Esc</kbd>
                    {{ __('Close') }}</span>
            </div> --}}

            <div class="flex flex-wrap gap-2 border-b border-line bg-white px-4 py-2.5">
                <template x-for="name in categories" :key="name">
                    <button type="button" @click="category = name" :aria-pressed="category === name"
                        :class="category === name ?
                            'bg-neutral-950 text-white ring-2 ring-neutral-950 ring-offset-2 font-semibold' :
                            'border border-neutral-300 bg-white text-neutral-800 hover:bg-neutral-50'"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm transition duration-150 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                        <x-ui.icon name="check" x-show="category === name" x-cloak class="size-4" />
                        <span x-text="name"></span>
                    </button>
                </template>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4 scrollbar-gutter:stable">
                <p x-show="notice" x-cloak role="status" aria-live="polite"
                    class="mb-3 flex items-center gap-2 rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-950 ring-1 ring-sky-200">
                    <x-ui.icon name="info" class="size-4" />
                    <span x-text="notice"></span>
                </p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button type="button" @click="addToCart(product)"
                            :disabled="product.stock < (product.unit?.allow_decimal ? 0.01 : 1)"
                            :aria-label="product.name + ', ' + usd(product.price) + (product.stock <= 0 ? ', out of stock' :
                                ', add to sale')"
                            :title="product.stock <= 0 ? '{{ __('Out of stock') }}' : (product.stock < (product.unit
                                ?.allow_decimal ? 0.01 : 1) ? '{{ __('Not enough stock for one item') }}' : '')"
                            class="group relative flex min-h-44 flex-col rounded-xl border border-line bg-white p-3 text-left shadow-sm transition duration-150 hover:border-neutral-950 hover:shadow-md active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:opacity-70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                            <span
                                class="absolute right-3 top-3 inline-flex size-9 items-center justify-center rounded-full bg-neutral-950 text-white transition group-hover:scale-105">
                                <x-ui.icon name="plus" class="size-5" />
                            </span>
                            <span
                                class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-lg bg-neutral-100 text-5xl">
                                <img x-show="product.image_url" :src="product.image_url" :alt="product.name"
                                    class="size-full object-cover" x-cloak>
                            </span>
                            <span class="mt-3 line-clamp-2 min-h-10 text-sm font-medium" x-text="product.name"></span>
                            <span class="mt-2 flex w-full items-end justify-between gap-2">
                                <span class="font-money text-base font-bold tabular-nums"
                                    x-text="usd(product.price)"></span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold"
                                    :class="product.stock <= 0 ? 'bg-red-100 text-red-950' : (product.stock <= product
                                        .min_stock ? 'bg-amber-100 text-amber-950' :
                                        'bg-neutral-100 text-neutral-800')">
                                    <x-ui.icon name="alert-triangle" x-show="product.stock <= product.min_stock"
                                        class="size-3.5" />
                                    <span
                                        x-text="product.stock <= 0 ? '{{ __('Out of stock') }}' : (product.stock <= product.min_stock ? product.stock + ' {{ __('left') }}' : product.stock + ' {{ __('in stock') }}')"></span>
                                </span>
                            </span>
                        </button>
                    </template>
                </div>
                <div x-show="! filteredProducts.length" x-cloak
                    class="mx-auto flex max-w-md flex-col items-center py-16 text-center">
                    <x-ui.icon name="search" class="size-8 text-neutral-400" />
                    <p class="mt-3 font-medium text-neutral-900">{{ __('No products found') }}</p>
                    <p class="mt-1 text-sm text-neutral-600">{{ __('Try another search or scan a barcode.') }}</p>
                    <button type="button" @click="query = ''; category = 'All'"
                        class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg border border-neutral-300 px-4 text-sm font-semibold text-neutral-900 hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950">
                        <x-ui.icon name="refresh" /> {{ __('Clear filters') }}
                    </button>
                </div>
            </div>
        </section>

        {{-- Fixed-size sidebar: height no longer depends on the number of cart lines --}}
        <aside
            class="flex h-[76dvh] min-h-0 w-full shrink-0 flex-col border-t border-line bg-white sm:h-[82dvh] lg:h-auto lg:w-100 lg:border-l lg:border-t-0">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-line px-5 py-3">
                <h2 class="flex items-center gap-2 text-base font-semibold">
                    <x-ui.icon name="cart" class="size-5" /> {{ __('Current sale') }}
                    <span class="ml-1 rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium tabular-nums"
                        x-text="itemCount + ' items'"></span>
                </h2>
                <div class="flex items-center gap-1">
                    <button type="button" x-show="heldSales.length" x-cloak @click="resumeSale()"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-neutral-800 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        title="{{ __('Resume held sale') }}">
                        <x-ui.icon name="play" class="size-4" />
                        <span x-text="'Resume · ' + heldSales.length"></span>
                    </button>
                    <button type="button" @click="holdSale()" :disabled="!cart.length"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-neutral-800 hover:bg-neutral-100 disabled:cursor-not-allowed disabled:text-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        title="{{ __('Hold current sale') }}">
                        <x-ui.icon name="pause" class="size-4" />
                        <span>{{ __('Hold') }}</span>
                    </button>
                    <button type="button" @click="requestClearCart('{{ __('Clear all items from this sale?') }}')"
                        :disabled="!cart.length"
                        class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:text-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700"
                        title="{{ __('Clear all items') }}">
                        <x-ui.icon name="trash" class="size-4" />
                        <span>{{ __('Clear') }}</span>
                    </button>
                </div>
            </div>

            {{-- Customer --}}
            <div class="border-b border-line px-5 py-3">
                <label class="mb-1.5 flex items-center gap-2 text-xs font-semibold text-neutral-700"
                    for="pos-customer">
                    <x-ui.icon name="user" /> {{ __('Customer') }}
                </label>
                <select id="pos-customer" x-model="customerId"
                    class="h-11 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                    <option value="">{{ __('Walk-in customer') }}</option>
                    <template x-for="customer in config.customers" :key="customer.id">
                        <option :value="customer.id" x-text="customer.name"></option>
                    </template>
                </select>
            </div>

            {{-- Cart lines (the only scrolling area) --}}
            <ul
                class="min-h-0 flex-1 divide-y divide-neutral-100 overflow-y-auto overscroll-contain px-4 scrollbar-gutter:stable sm:px-5">
                <template x-for="line in cart" :key="line.id">
                    {{-- Fixed 3-column grid: [image] [name + qty controls] [remove + line total] --}}
                    <li :class="recentlyAddedId === line.id ? 'bg-emerald-50 ring-1 ring-inset ring-emerald-300' : 'bg-white'"
                        class="grid grid-cols-[2.75rem_minmax(0,1fr)_5.5rem] items-center gap-x-3 gap-y-2 rounded-lg px-2 py-3 transition-colors duration-200">
                        {{-- Image --}}
                        <span
                            class="row-span-2 flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100 text-2xl">
                            <img x-show="line.image" :src="line.image" :alt="line.name"
                                class="size-full object-cover" x-cloak>
                            <span x-show="!line.image" class="flex items-center justify-center" aria-hidden="true">
                                <span x-show="line.emoji" x-text="line.emoji"></span>
                                <x-ui.icon name="package" x-show="!line.emoji" class="size-6 text-neutral-500" />
                            </span>
                        </span>

                        {{-- Name + unit price --}}
                        <div class="col-start-2 row-start-1 min-w-0">
                            <p class="truncate text-sm font-medium" x-text="line.name"></p>
                            <p class="font-money truncate text-xs tabular-nums text-ink-muted"
                                x-text="usd(line.price) + ' each'"></p>
                        </div>

                        {{-- Remove --}}
                        <button type="button" @click="removeFromCart(line)"
                            class="col-start-3 row-start-1 inline-flex min-h-11 items-center justify-center justify-self-end gap-1.5 rounded-lg px-2 text-xs font-semibold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700"
                            aria-label="{{ __('Remove item') }}" title="{{ __('Remove item') }}">
                            <x-ui.icon name="trash" class="size-4" />
                            <span>{{ __('Remove') }}</span>
                        </button>

                        {{-- Quantity controls --}}
                        <div role="group"
                            class="col-start-2 row-start-2 flex w-fit shrink-0 items-center rounded-lg border border-neutral-300"
                            :aria-label="'Quantity controls for ' + line.name">
                            <span
                                class="shrink-0 px-2 text-[10px] font-semibold uppercase tracking-wide text-neutral-500">{{ __('Qty') }}</span>
                            <button type="button" @click="decrease(line)"
                                class="inline-flex size-11 shrink-0 items-center justify-center rounded-l-lg text-neutral-800 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-neutral-950"
                                aria-label="{{ __('Decrease quantity') }}" title="{{ __('Decrease quantity') }}">
                                <x-ui.icon name="minus" class="size-5" />
                            </button>
                            <input x-show="line.allowDecimal" type="number" min="0.01" step="0.01"
                                :max="line.stock" x-model.number="line.quantity"
                                @change="line.quantity = Number.isFinite(Number($event.target.value)) ? Math.max(0.01, Math.min(line.stock, Math.round(Number($event.target.value) * 100) / 100)) : 0.01"
                                class="font-money h-11 w-16 shrink-0 border-0 bg-transparent p-0 text-center text-sm tabular-nums focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                                :aria-label="'Quantity for ' + line.name">
                            <span x-show="!line.allowDecimal"
                                class="font-money inline-block min-w-9 shrink-0 px-1 text-center text-sm tabular-nums"
                                x-text="line.quantity"></span>
                            <button type="button" @click="increase(line)" :disabled="line.quantity >= line.stock"
                                class="inline-flex size-11 shrink-0 items-center justify-center rounded-r-lg text-neutral-800 hover:bg-neutral-100 disabled:cursor-not-allowed disabled:text-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-neutral-950"
                                aria-label="{{ __('Increase quantity') }}" title="{{ __('Increase quantity') }}">
                                <x-ui.icon name="plus" class="size-5" />
                            </button>
                        </div>

                        {{-- Line total --}}
                        <span
                            class="font-money col-start-3 row-start-2 whitespace-nowrap text-right text-sm font-medium tabular-nums"
                            x-text="usd(line.price * line.quantity)"></span>
                    </li>
                </template>
                <li x-show="! cart.length" x-cloak class="py-8">
                    <x-ui.empty-state icon="cart" :title="__('Your sale is empty')" :description="__('Scan a barcode or tap a product to add it here.')" />
                </li>
            </ul>

            <div x-show="removedLine" x-cloak role="status" aria-live="polite"
                class="mx-4 mb-2 flex shrink-0 items-center justify-between gap-3 rounded-lg bg-neutral-950 px-3 py-2 text-sm text-white">
                <span>{{ __('Item removed') }}</span>
                <button type="button" @click="undoRemove()"
                    class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2 font-semibold underline underline-offset-2 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    <x-ui.icon name="undo" /> {{ __('Undo') }}
                </button>
            </div>

            {{-- Pinned checkout panel --}}
            <div
                class="shrink-0 space-y-3 border-t border-line bg-white px-5 py-3 text-sm shadow-[0_-4px_12px_rgba(0,0,0,0.04)]">

                {{-- Discount --}}
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2 font-medium text-neutral-800">
                        <x-ui.icon name="percent" /> {{ __('Discount') }}
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (config('vpos.discount_options') as $percent)
                            <button type="button" @click="discountPercent = {{ $percent }}"
                                :aria-pressed="discountPercent === {{ $percent }}"
                                :class="discountPercent === {{ $percent }} ?
                                    'bg-neutral-950 text-white ring-2 ring-neutral-950 ring-offset-1 font-bold' :
                                    'border border-neutral-300 bg-white text-neutral-800 hover:bg-neutral-50'"
                                class="inline-flex min-h-10 items-center gap-1 rounded-lg px-3 text-xs font-medium transition duration-150 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                                <x-ui.icon name="check" x-show="discountPercent === {{ $percent }}" x-cloak
                                    class="size-3.5" />
                                {{ $percent }}%
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Totals --}}
                <dl class="space-y-1">
                    <div class="flex justify-between tabular-nums">
                        <dt class="text-ink-muted">Subtotal</dt>
                        <dd class="font-money" x-text="usd(subtotal)"></dd>
                    </div>
                    <div class="flex justify-between tabular-nums" x-show="discountPercent > 0" x-cloak>
                        <dt class="text-ink-muted" x-text="'Discount · ' + discountPercent + '%'"></dt>
                        <dd class="font-money" x-text="'−' + usd(discountAmount)"></dd>
                    </div>
                    <div class="flex justify-between tabular-nums">
                        <dt class="text-ink-muted">VAT · {{ config('vpos.vat_rate') }}%</dt>
                        <dd class="font-money" x-text="usd(vatAmount)"></dd>
                    </div>
                </dl>
                <div class="flex items-end justify-between border-t border-line pt-3">
                    <span class="inline-flex items-center gap-2 text-base font-bold">
                        <x-ui.icon name="wallet" class="size-5" /> {{ __('Total') }}
                    </span>
                    <span class="text-right">
                        <span class="font-money block text-3xl font-bold tabular-nums tracking-tight"
                            x-text="usd(total)"></span>
                        <span class="font-money text-xs tabular-nums text-ink-muted" x-text="khr(total)"></span>
                    </span>
                </div>

                {{-- Payment methods --}}
                <div class="grid grid-cols-3 gap-2" role="group" aria-label="{{ __('Payment method') }}">
                    @foreach ([
        'cash' => [__('Cash'), 'banknote', 'bg-emerald-600 hover:bg-emerald-500', 'bg-emerald-700 hover:bg-emerald-800'],
        'khqr' => [__('KHQR'), 'qr-code', 'bg-red-600 hover:bg-red-500', 'bg-red-700 hover:bg-red-800'],
        'card' => [__('Card'), 'credit-card', 'bg-blue-600 hover:bg-blue-500', 'bg-blue-700 hover:bg-blue-800'],
    ] as $key => [$label, $icon, $activeColor, $inactiveColor])
                        <button type="button" :aria-pressed="method === '{{ $key }}'"
                            @click="selectPaymentMethod('{{ $key }}')"
                            :class="method === '{{ $key }}' ?
                                '{{ $activeColor }} ring-2 ring-offset-2 ring-white shadow-md' :
                                '{{ $inactiveColor }}'"
                            class="relative flex min-h-16 flex-col items-center justify-center gap-1 rounded-xl text-sm font-semibold text-white transition duration-150 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            <x-ui.icon :name="$icon" class="size-6" />
                            {{ $label }}
                            <x-ui.icon name="check" x-show="method === '{{ $key }}'" x-cloak
                                class="absolute right-1.5 top-1.5 size-4" />
                        </button>
                    @endforeach
                </div>

                {{-- Cash --}}
                <div x-show="method === 'cash'" x-cloak class="space-y-2.5">
                    <div class="flex items-center gap-2">
                        <div class="relative min-w-0 flex-1">
                            <label for="cash-received"
                                class="mb-1.5 flex items-center gap-2 text-xs font-semibold text-neutral-700">
                                <x-ui.icon name="banknote" /> {{ __('Cash received') }}
                            </label>
                            <div class="relative">
                                <span
                                    class="font-money pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-600">$</span>
                                <input id="cash-received" x-ref="received" x-model="received" type="text"
                                    inputmode="decimal" autocomplete="off" placeholder="{{ __('Enter amount') }}"
                                    @keydown.enter.prevent="canComplete && completeSale()"
                                    class="font-money h-12 w-full rounded-lg border border-neutral-300 pl-7 pr-3 text-lg tabular-nums focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950 focus-visible:ring-offset-2">
                            </div>
                        </div>
                        <div class="min-w-28 shrink-0 text-right">
                            <p class="text-xs font-semibold text-neutral-700">{{ __('Change to give') }}</p>
                            <p class="font-money text-xl font-bold tabular-nums text-emerald-800"
                                x-text="usd(change)"></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="amount in quickAmounts" :key="amount">
                            <button type="button" @click="received = String(Number(amount.toFixed(2)))"
                                class="font-money min-h-10 rounded-lg border border-neutral-300 px-3 text-xs font-medium tabular-nums text-neutral-900 transition hover:bg-neutral-50 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                                x-text="amount === total ? 'Exact ' + usd(amount) : usd(amount)"></button>
                        </template>
                    </div>
                    <p class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-900 ring-1 ring-red-200"
                        x-show="receivedAmount > 0 && receivedAmount < total" x-cloak role="alert">
                        <x-ui.icon name="alert-triangle" class="size-4" />
                        <span>{{ __('Customer still owes') }} <span class="font-money font-bold tabular-nums"
                                x-text="usd(total - receivedAmount)"></span>.</span>
                    </p>
                </div>

                {{-- KHQR --}}
                <p x-show="method === 'khqr'" x-cloak
                    class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-950 ring-1 ring-red-200">
                    <x-ui.icon name="qr-code" class="size-5 shrink-0" />
                    Customer scans your merchant QR in any Bakong app. Confirm the payment before completing.
                </p>

                {{-- Card --}}
                <p x-show="method === 'card'" x-cloak
                    class="flex items-center gap-2 rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-950 ring-1 ring-sky-200">
                    <x-ui.icon name="credit-card" class="size-5 shrink-0" />
                    Process on your card terminal, then complete the sale once approved.
                </p>

                <div x-show="checkoutError" x-cloak role="alert"
                    class="flex items-start gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-950 ring-1 ring-red-200">
                    <x-ui.icon name="alert-triangle" class="mt-0.5 size-4" />
                    <span class="min-w-0 flex-1" x-text="checkoutError"></span>
                    <button type="button" @click="completeSale()" :disabled="completing"
                        class="inline-flex min-h-9 items-center gap-1 rounded-md px-2 font-semibold underline disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-800">
                        <x-ui.icon name="refresh" /> {{ __('Retry') }}
                    </button>
                </div>

                <x-ui.button size="lg" class="w-full" @click="completeSale()" x-bind:disabled="!canComplete">
                    <span x-show="completing" x-cloak
                        class="mr-2 inline-flex size-4 animate-spin rounded-full border-2 border-current border-r-transparent"
                        aria-hidden="true"></span>
                    <x-ui.icon name="check" x-show="!completing" class="mr-2 size-5" />
                    <span x-text="completing ? 'Saving sale…' : 'Complete sale · ' + usd(total)"></span>
                </x-ui.button>
                <p x-show="!cart.length" class="text-center text-xs font-medium text-neutral-700">
                    {{ __('Add items first to complete this sale.') }}
                </p>
                <p x-show="cart.length && method === 'cash' && receivedAmount < total" x-cloak
                    class="text-center text-xs font-medium text-red-900">
                    {{ __('Enter') }} <span class="font-money tabular-nums"
                        x-text="usd(total - receivedAmount)"></span>
                    {{ __('more to complete the sale.') }}
                </p>
                <p x-show="canComplete" x-cloak class="text-center text-xs font-medium text-emerald-800">
                    {{ __('Ready to complete sale.') }}
                </p>
                {{-- <p class="text-center text-[11px] text-neutral-500">
                    <kbd class="rounded border border-neutral-300 bg-white px-1.5 py-0.5 font-mono">F10</kbd>
                    {{ __('Complete sale') }}
                </p> --}}
            </div>
        </aside>

        <div x-show="scannerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="barcode-scanner-title"
            @keydown.tab.prevent="trapDialogFocus($event)">
            <button type="button" tabindex="-1" class="absolute inset-0 bg-neutral-950/60"
                aria-label="{{ __('Close camera scanner') }}" @click="stopBarcodeScanner()"></button>
            <div class="relative w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                <header class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="barcode-scanner-title" class="font-semibold text-neutral-950">Scan product barcode
                        </h2>
                        <p class="mt-1 text-sm text-ink-muted">Point camera at the barcode. Items will be added to this
                            sale.</p>
                    </div>
                    <button type="button" data-camera-close @click="stopBarcodeScanner()"
                        class="inline-flex size-11 items-center justify-center rounded-lg text-neutral-700 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        aria-label="{{ __('Close camera scanner') }}" title="{{ __('Close') }}">
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </header>

                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <label for="barcode-camera" class="inline-flex items-center gap-2 text-sm font-medium">
                        <x-ui.icon name="camera" /> {{ __('Camera') }}
                    </label>

                    <select id="barcode-camera" x-model="cameraFacingMode" @change="switchBarcodeCamera()"
                        class="min-h-11 rounded-lg border border-neutral-300 px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950">
                        <option value="environment">Rear camera</option>
                        <option value="user">Front camera</option>
                    </select>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl bg-neutral-950 min-h-65 flex items-center justify-center">
                    <div id="pos-barcode-reader" class="w-full"></div>
                    <div x-show="scannerLoading"
                        class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-neutral-950/80 text-white">
                        <x-ui.icon name="scan" class="size-8 animate-pulse text-neutral-300" />
                        <span class="text-sm font-medium text-neutral-300">Starting camera…</span>
                    </div>
                    {{-- Reticle / Aiming Guide with top-to-bottom laser animation --}}
                    <div x-show="!scannerLoading && !scannerMessage"
                        class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div
                            class="relative h-32 w-64 rounded-xl border-2 border-emerald-400/90 shadow-[0_0_16px_rgba(52,211,153,0.35)] overflow-hidden">
                            <div class="scanner-laser"></div>
                        </div>
                    </div>
                </div>

                <p x-show="scannedFeedback" x-cloak
                    class="mt-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-center text-xs font-semibold text-emerald-800"
                    x-text="scannedFeedback"></p>
                <div x-show="scannerMessage" x-cloak role="alert"
                    class="mt-2 flex items-start justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-950 ring-1 ring-amber-200">
                    <span x-text="scannerMessage"></span>
                    <button type="button" @click="startBarcodeScanner()"
                        class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-md px-2 font-semibold underline focus-visible:ring-2 focus-visible:ring-amber-900">
                        <x-ui.icon name="refresh" /> {{ __('Retry') }}
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs font-medium text-neutral-700 cursor-pointer">
                            <input type="checkbox" x-model="continuousScan"
                                class="rounded border-neutral-300 text-neutral-950 focus:ring-neutral-950">
                            <span>Keep scanner open for multiple items</span>
                        </label>
                        <input type="file" x-ref="barcodeFileInput" accept="image/*" class="hidden"
                            @change="scanFromFile($event)">
                        <button type="button"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg px-2 text-sm font-medium text-neutral-800 underline underline-offset-2 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                            @click="$refs.barcodeFileInput.click()">
                            <x-ui.icon name="upload" /> {{ __('Upload image') }}
                        </button>
                    </div>
                    <x-ui.button type="button" variant="secondary" size="sm"
                        @click="stopBarcodeScanner()">Close scanner</x-ui.button>
                </div>
            </div>
        </div>

        <div x-show="paymentOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="payment-modal-title"
            @keydown.tab.prevent="trapDialogFocus($event)" @click.self="closeDialog('paymentOpen')">
            <button type="button" tabindex="-1" class="absolute inset-0 bg-neutral-950/60"
                aria-label="{{ __('Close payment dialog') }}" @click="closeDialog('paymentOpen')"></button>
            <div class="relative max-h-full w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
                <header class="flex items-start justify-between border-b border-line px-6 py-4">
                    <div>
                        <h2 id="payment-modal-title" class="text-base font-semibold">{{ __('Collect payment') }}</h2>
                        <p class="text-sm text-ink-muted"><span x-text="itemCount"></span> items</p>
                    </div>
                    <button type="button" @click="closeDialog('paymentOpen')"
                        class="inline-flex size-11 items-center justify-center rounded-lg hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        aria-label="{{ __('Close payment dialog') }}" title="{{ __('Close') }}">
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </header>
                <div class="grid gap-6 p-6 md:grid-cols-5">
                    <div class="space-y-4 md:col-span-3">
                        <p x-show="checkoutError" x-cloak role="alert" x-text="checkoutError"
                            class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800"></p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([
        'cash' => [__('Cash'), 'banknote', 'bg-emerald-600 ring-emerald-700', 'border-emerald-200 bg-emerald-50 text-emerald-950'],
        'khqr' => [__('KHQR'), 'qr-code', 'bg-red-600 ring-red-700', 'border-red-200 bg-red-50 text-red-950'],
        'card' => [__('Card'), 'credit-card', 'bg-blue-600 ring-blue-700', 'border-blue-200 bg-blue-50 text-blue-950'],
    ] as $key => [$label, $icon, $selectedColor, $unselectedColor])
                                <button type="button" @click="selectPaymentMethod('{{ $key }}')"
                                    :aria-pressed="method === '{{ $key }}'"
                                    :class="method === '{{ $key }}' ?
                                        '{{ $selectedColor }} text-white ring-2 ring-offset-2' :
                                        '{{ $unselectedColor }} hover:brightness-95'"
                                    class="relative flex min-h-16 flex-col items-center justify-center gap-1.5 rounded-xl border p-3 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950">
                                    <x-ui.icon :name="$icon" class="size-6" /> {{ $label }}
                                    <x-ui.icon name="check" x-show="method === '{{ $key }}'" x-cloak
                                        class="absolute right-2 top-2 size-4" />
                                </button>
                            @endforeach
                        </div>

                        <div x-show="method === 'cash'" class="space-y-3">
                            <label for="payment-cash-received" class="flex items-center gap-2 text-sm font-semibold">
                                <x-ui.icon name="banknote" /> {{ __('Cash received') }}
                            </label>
                            <input id="payment-cash-received" x-ref="paymentReceived" x-model="received"
                                type="text" inputmode="decimal"
                                @keydown.enter.prevent="canComplete && completeSale()"
                                class="font-money h-12 w-full rounded-lg border border-neutral-300 px-3 text-lg tabular-nums focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                                placeholder="{{ __('Enter amount') }}">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="amount in quickAmounts" :key="amount">
                                    <button type="button" @click="received = String(Number(amount.toFixed(2)))"
                                        class="font-money min-h-11 rounded-lg border border-neutral-300 px-3 text-sm tabular-nums text-neutral-900 hover:bg-neutral-50 focus-visible:ring-2 focus-visible:ring-neutral-950"
                                        x-text="amount === total ? 'Exact ' + usd(amount) : usd(amount)"></button>
                                </template>
                            </div>
                            <p x-show="receivedAmount > 0 && receivedAmount < total" x-cloak role="alert"
                                class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-950 ring-1 ring-red-200">
                                <x-ui.icon name="alert-triangle" class="size-4" />
                                {{ __('Customer still owes') }} <span class="font-money font-bold tabular-nums"
                                    x-text="usd(total - receivedAmount)"></span>.
                            </p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', 'back'] as $key)
                                    <button type="button" @click="pressKey('{{ $key }}')"
                                        @if ($key === 'back') aria-label="{{ __('Delete last digit') }}" title="{{ __('Delete last digit') }}" @endif
                                        class="font-money flex h-12 min-h-11 items-center justify-center rounded-lg bg-neutral-100 text-lg text-neutral-950 transition duration-150 hover:bg-neutral-200 active:scale-95 focus-visible:ring-2 focus-visible:ring-neutral-950 motion-reduce:transition-none">
                                        @if ($key === 'back')
                                            <x-ui.icon name="chevron-left" class="size-5" />
                                        @else
                                            {{ $key }}
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="method === 'khqr'" x-cloak
                            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-400 p-8 text-center">
                            <x-ui.icon name="qr-code" class="size-32 text-neutral-950" />
                            <p class="text-sm font-medium">Ask the customer to scan with any Bakong banking app.</p>
                            <p class="text-xs text-ink-muted">Ask the customer to scan your merchant QR and verify the
                                payment before completing the sale.</p>
                        </div>

                        <div x-show="method === 'card'" x-cloak
                            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-400 p-8 text-center">
                            <x-ui.icon name="credit-card" class="size-12" />
                            <p class="text-sm font-medium">Process the payment on your terminal, then complete the sale
                                after it is approved.</p>
                        </div>
                    </div>

                    <div class="space-y-4 md:col-span-2">
                        <div class="rounded-xl bg-neutral-100 p-4">
                            <p class="text-sm text-ink-muted">Amount due</p>
                            <p class="font-money text-3xl font-semibold" x-text="usd(total)"></p>
                            <p class="font-money text-sm text-ink-muted" x-text="khr(total)"></p>
                        </div>
                        <div x-show="method === 'cash'"
                            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-sm font-semibold text-emerald-950">{{ __('Change to give') }}</p>
                            <p class="font-money text-3xl font-bold tabular-nums text-emerald-800"
                                x-text="usd(change)"></p>
                            <p class="font-money text-sm text-emerald-900" x-text="khr(change)"></p>
                        </div>
                        <x-ui.button size="lg" class="w-full" @click="completeSale()"
                            x-bind:disabled="!canComplete">
                            <span x-show="completing" x-cloak
                                class="mr-2 inline-flex size-4 animate-spin rounded-full border-2 border-current border-r-transparent"
                                aria-hidden="true"></span>
                            <x-ui.icon name="check" x-show="!completing" class="mr-2 size-5" />
                            <span x-text="completing ? 'Saving sale…' : 'Complete sale'"></span>
                        </x-ui.button>
                        <p x-show="!cart.length" class="text-center text-xs text-neutral-700">
                            {{ __('Add items first to complete this sale.') }}</p>
                        <p x-show="cart.length && method === 'cash' && receivedAmount < total" x-cloak
                            class="text-center text-xs font-medium text-red-900">
                            {{ __('Enter') }} <span class="font-money tabular-nums"
                                x-text="usd(total - receivedAmount)"></span>
                            {{ __('more to complete the sale.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="receiptOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="receipt-modal-title"
            @keydown.tab.prevent="trapDialogFocus($event)" @click.self="closeDialog('receiptOpen')">
            <button type="button" tabindex="-1" class="absolute inset-0 bg-neutral-950/60"
                aria-label="{{ __('Close receipt') }}" @click="closeDialog('receiptOpen')"></button>
            <div class="relative flex max-h-full w-full max-w-md flex-col rounded-xl bg-white shadow-xl">
                <header class="border-b border-line px-6 py-4 text-center">
                    <span
                        class="mx-auto flex size-10 items-center justify-center rounded-full bg-emerald-700 text-white"><x-ui.icon
                            name="check" class="size-5" /></span>
                    <h2 id="receipt-modal-title"
                        class="mt-2 flex items-center justify-center gap-2 text-base font-semibold">
                        <x-ui.icon name="receipt" /> {{ __('Payment received') }}
                    </h2>
                    <p class="text-sm text-ink-muted" x-text="lastSale && lastSale.number"></p>
                    <button type="button" @click="closeDialog('receiptOpen')"
                        class="absolute right-3 top-3 inline-flex size-11 items-center justify-center rounded-lg text-neutral-700 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        aria-label="{{ __('Close receipt') }}" title="{{ __('Close') }}">
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </header>
                <div class="overflow-y-auto bg-neutral-50 p-6">
                    <div id="receipt" class="mx-auto w-full max-w-[300px] bg-white p-5 text-xs shadow-sm"
                        x-show="lastSale">
                        <template x-if="lastSale">
                            <div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold">{{ config('vpos.store') }}</p>
                                    <p class="text-ink-muted" x-text="lastSale.placedAt"></p>
                                    <p class="text-ink-muted" x-text="lastSale.customer"></p>
                                </div>
                                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                                <template x-for="line in lastSale.lines" :key="line.id">
                                    <div class="mb-1.5 flex justify-between gap-2"><span
                                            x-text="line.quantity + ' × ' + line.name"></span><span class="font-money"
                                            x-text="usd(line.price * line.quantity)"></span></div>
                                </template>
                                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                                <div class="flex justify-between"><span>Subtotal</span><span class="font-money"
                                        x-text="usd(lastSale.subtotal)"></span></div>
                                <div class="flex justify-between" x-show="lastSale.discountPercent > 0"><span
                                        x-text="'Discount ' + lastSale.discountPercent + '%'"></span><span
                                        class="font-money" x-text="'−' + usd(lastSale.discountAmount)"></span></div>
                                <div class="flex justify-between"><span>VAT
                                        {{ config('vpos.vat_rate') }}%</span><span class="font-money"
                                        x-text="usd(lastSale.vatAmount)"></span></div>
                                <div class="mt-2 flex justify-between text-sm font-semibold"><span>Total</span><span
                                        class="font-money" x-text="usd(lastSale.total)"></span></div>
                                <div class="flex justify-between text-ink-muted"><span></span><span class="font-money"
                                        x-text="khr(lastSale.total)"></span></div>
                                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                                <div class="flex justify-between"><span
                                        x-text="'Paid by ' + lastSale.method.toUpperCase()"></span><span
                                        class="font-money" x-text="usd(lastSale.received)"></span></div>
                                <div class="flex justify-between"><span>Change</span><span class="font-money"
                                        x-text="usd(lastSale.change)"></span></div>
                                <p class="mt-4 text-center text-ink-muted">Thank you. Come again!</p>
                            </div>
                        </template>
                    </div>
                </div>
                <footer class="grid grid-cols-1 gap-2 border-t border-line p-4 sm:grid-cols-2">
                    <x-ui.button data-receipt-focus icon="plus" @click="startNewSale()">
                        {{ __('New sale') }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" icon="printer" @click="window.print()">
                        {{ __('Print receipt') }}
                    </x-ui.button>
                </footer>
            </div>
        </div>

        <div x-show="mobileModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog" aria-modal="true" aria-labelledby="mobile-scanner-modal-title"
            @keydown.tab.prevent="trapDialogFocus($event)" @click.self="closeDialog('mobileModalOpen')">
            <button type="button" tabindex="-1" class="absolute inset-0 bg-neutral-950/60 backdrop-blur-xs"
                aria-label="{{ __('Close mobile scanner') }}" @click="closeDialog('mobileModalOpen')"></button>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <header class="flex items-center justify-between pb-4 border-b border-line">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex size-9 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900">
                            <x-ui.icon name="smartphone" class="size-5" />
                        </span>
                        <div>
                            <h2 id="mobile-scanner-modal-title"
                                class="text-base font-semibold text-neutral-950 leading-tight">Mobile Barcode Scanner
                            </h2>
                            <p class="text-xs text-ink-muted">Scan products with your phone into this POS cart</p>
                        </div>
                    </div>
                    <button type="button" data-mobile-close @click="closeDialog('mobileModalOpen')"
                        class="inline-flex size-11 items-center justify-center rounded-lg text-neutral-700 hover:text-neutral-950 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-950"
                        aria-label="{{ __('Close mobile scanner') }}" title="{{ __('Close') }}">
                        <x-ui.icon name="x" class="size-5" />
                    </button>
                </header>

                {{-- Connection Status Banner --}}
                <div class="my-4 flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-medium"
                    :class="isMobileConnected ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
                        'bg-neutral-100 text-neutral-700 border border-neutral-200'">
                    <div class="flex items-center gap-2">
                        <span class="flex size-2 rounded-full"
                            :class="isMobileConnected ? 'bg-emerald-500 animate-pulse' : 'bg-neutral-400'"></span>
                        <span
                            x-text="isMobileConnected ? 'Mobile scanner connected & active' : 'No mobile device connected'"></span>
                    </div>
                    <button type="button" x-show="isMobileConnected" @click="disconnectMobileScanner()"
                        class="font-semibold text-rose-600 hover:underline">
                        Disconnect
                    </button>
                </div>

                <div x-show="mobileError" x-cloak role="alert"
                    class="mb-4 flex items-start justify-between gap-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-950 ring-1 ring-red-200">
                    <span class="flex items-center gap-2"><x-ui.icon name="alert-triangle" /> <span
                            x-text="mobileError"></span></span>
                    <button type="button" @click="fetchMobileSession()"
                        class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-md px-2 font-semibold underline focus-visible:ring-2 focus-visible:ring-red-800">
                        <x-ui.icon name="refresh" /> {{ __('Retry') }}
                    </button>
                </div>

                {{-- Tabs: QR Code vs Direct Pairing Code --}}
                <div class="flex border-b border-line mb-4">
                    <button type="button" @click="pairingTab = 'qr'"
                        :class="pairingTab === 'qr' ? 'border-neutral-950 text-neutral-950 font-semibold' :
                            'border-transparent text-ink-muted hover:text-neutral-800'"
                        class="flex-1 py-2 text-center text-xs border-b-2 transition">
                        Method A: QR Code
                    </button>
                    <button type="button" @click="pairingTab = 'code'"
                        :class="pairingTab === 'code' ? 'border-neutral-950 text-neutral-950 font-semibold' :
                            'border-transparent text-ink-muted hover:text-neutral-800'"
                        class="flex-1 py-2 text-center text-xs border-b-2 transition">
                        Method B: Direct Code
                    </button>
                </div>

                {{-- TAB 1: QR Code Pairing --}}
                <div x-show="pairingTab === 'qr'" class="flex flex-col items-center text-center space-y-3">
                    <p class="text-xs text-ink-muted">Scan this QR code with your smartphone camera to connect:</p>

                    <div
                        class="relative flex size-52 items-center justify-center rounded-2xl border-2 border-dashed border-neutral-300 bg-white p-2 shadow-inner">
                        <div id="desktop-pairing-qr" class="size-full flex items-center justify-center"></div>
                        <div x-show="mobileLoading"
                            class="absolute inset-0 bg-white/90 flex flex-col items-center justify-center gap-2">
                            <span
                                class="size-6 rounded-full border-2 border-neutral-900 border-t-transparent animate-spin"></span>
                            <span class="text-xs text-neutral-600">Generating QR…</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-ink-muted">
                        <span x-text="countdownText"></span>
                        <span>·</span>
                        <button type="button" @click="regenerateMobileCode()"
                            class="inline-flex items-center gap-1 font-semibold text-neutral-900 hover:underline">
                            <x-ui.icon name="refresh" class="size-3.5" />
                            <span>Refresh QR</span>
                        </button>
                    </div>
                </div>

                <div x-show="pairingTab === 'code'" class="flex flex-col items-center text-center space-y-4 py-2">
                    <p class="text-xs text-ink-muted">On your phone, open <span
                            class="font-mono font-medium text-neutral-900 underline">{{ url('/pos/scanner') }}</span>
                        and enter:</p>

                    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-8 py-5">
                        <p class="font-mono text-3xl font-bold tracking-widest text-neutral-950"
                            x-text="mobileSession?.pairing_code || '------'"></p>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-ink-muted">
                        <span x-text="countdownText"></span>
                        <span>·</span>
                        <button type="button" @click="regenerateMobileCode()"
                            class="inline-flex items-center gap-1 font-semibold text-neutral-900 hover:underline">
                            <x-ui.icon name="refresh" class="size-3.5" />
                            <span>Generate new code</span>
                        </button>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-line flex justify-end">
                    <x-ui.button variant="secondary" size="sm"
                        @click="closeDialog('mobileModalOpen')">{{ __('Done') }}</x-ui.button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.pos>
