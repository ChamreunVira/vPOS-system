<x-layouts.app :title="$order['number']">
    <x-ui.page-header :title="'Order '.$order['number']" :subtitle="$order['placed_at']->format('F j, Y · g:i A').' · Cashier '.$order['cashier']" :back="route('orders.index')">
        <x-ui.button variant="secondary" icon="printer" onclick="window.print()">Print receipt</x-ui.button>
        @if (in_array($order['status'], ['completed', 'partially_refunded'], true))
            <x-ui.button variant="danger" icon="undo" x-on:click="$dispatch('open-modal', 'refund')">Refund or return</x-ui.button>
        @endif
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Order items" :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Item</th><th class="text-right">Price</th><th class="text-right">Qty</th><th class="text-right">Total</th></tr></x-slot:head>
                    @foreach ($order['items'] as $item)
                        <tr>
                            <td><span class="flex items-center gap-3"><span class="flex size-9 items-center justify-center rounded-lg bg-neutral-100 text-lg">{{ $item['emoji'] }}</span><span><span class="block font-medium">{{ $item['name'] }}</span><span class="font-money text-xs text-ink-muted">{{ $item['sku'] }}</span></span></span></td>
                            <td class="text-right"><x-ui.money :amount="$item['price']" /></td>
                            <td class="text-right font-money">{{ $item['quantity'] }}</td>
                            <td class="text-right"><x-ui.money :amount="$item['line_total']" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>

            <x-ui.card title="Status">
                <div class="flex flex-wrap gap-8 text-sm">
                    <div><p class="mb-1 text-ink-muted">Order</p><x-ui.status :status="$order['status']" /></div>
                    <div><p class="mb-1 text-ink-muted">Payment</p><x-ui.status :status="$order['payment_status']" /></div>
                    <div><p class="mb-1 text-ink-muted">Method</p><span class="font-medium">{{ $order['method'] === 'khqr' ? 'KHQR' : ucfirst($order['method']) }}</span></div>
                    <div><p class="mb-1 text-ink-muted">Customer</p><span class="font-medium">{{ $order['customer'] }}</span></div>
                </div>
            </x-ui.card>
        </div>

        {{-- Receipt preview (also the print target) --}}
        <div>
            <div id="receipt" class="rounded-xl border border-line bg-white p-5 text-xs">
                <div class="text-center">
                    <p class="text-sm font-semibold">{{ config('vpos.store') }}</p>
                    <p class="text-ink-muted">{{ $order['number'] }} · {{ $order['placed_at']->format('d M Y, H:i') }}</p>
                </div>
                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                @foreach ($order['items'] as $item)
                    <div class="mb-1.5 flex justify-between gap-2"><span>{{ $item['quantity'] }} × {{ $item['name'] }}</span><x-ui.money :amount="$item['line_total']" /></div>
                @endforeach
                <div class="my-3 border-t border-dashed border-neutral-400"></div>
                <div class="flex justify-between"><span>Subtotal</span><x-ui.money :amount="$order['subtotal']" /></div>
                @if ($order['discount'] > 0)<div class="flex justify-between"><span>Discount {{ $order['discount_percent'] }}%</span><x-ui.money :amount="-$order['discount']" /></div>@endif
                <div class="flex justify-between"><span>VAT {{ config('vpos.vat_rate') }}%</span><x-ui.money :amount="$order['vat']" /></div>
                <div class="mt-2 flex justify-between text-sm font-semibold"><span>Total</span><x-ui.money :amount="$order['total']" /></div>
                <div class="flex justify-end text-ink-muted"><x-ui.money :amount="$order['total']" currency="khr" /></div>
                <p class="mt-4 text-center text-ink-muted">Thank you. Come again!</p>
            </div>
        </div>
    </div>

    {{-- Refund / return modal: pick quantities per line, see the refund amount update. --}}
    <x-ui.modal name="refund" title="Refund or return" description="Choose what comes back. Stock is restored when you confirm." size="lg">
        <form id="refund-form" method="POST" action="{{ route('orders.refund', $order['id']) }}"
              x-data="{ lines: @js(collect($order['items'])->map(fn ($i) => ['name' => $i['name'], 'price' => $i['price'], 'max' => $i['quantity'], 'qty' => 0])->all()),
                        get total() { return this.lines.reduce((sum, l) => sum + l.price * l.qty, 0) } }" class="space-y-5">
            @csrf
            <ul class="divide-y divide-neutral-100">
                <template x-for="(line, index) in lines" :key="index">
                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                        <div><p class="font-medium" x-text="line.name"></p><p class="text-xs text-ink-muted" x-text="'Bought ' + line.max + ' · $' + line.price.toFixed(2) + ' each'"></p></div>
                        <input type="number" min="0" :max="line.max" x-model.number="line.qty" :name="'items[' + index + '][quantity]'" class="font-money h-9 w-20 rounded-lg border border-neutral-300 px-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900" aria-label="Quantity to return">
                    </li>
                </template>
            </ul>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select label="Reason" name="reason" :options="['Customer changed mind', 'Damaged item', 'Wrong item', 'Expired product']" />
                <x-form.select label="Refund method" name="refund_method" :options="['cash' => 'Cash', 'original' => 'Original payment method', 'store_credit' => 'Store credit']" />
            </div>
            <div class="flex items-center justify-between rounded-lg bg-neutral-100 px-4 py-3">
                <span class="text-sm text-ink-muted">Refund total</span>
                <span class="font-money text-lg font-semibold" x-text="'$' + total.toFixed(2)"></span>
            </div>
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'refund')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="refund-form">Confirm refund</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
