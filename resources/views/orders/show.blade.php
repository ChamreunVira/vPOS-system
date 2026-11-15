<x-layouts.app :title="$order->order_number">
    <x-ui.page-header :title="'Order ' . $order->order_number" :subtitle="$order->placed_at->format('F j, Y · g:i A') . ' · Cashier ' . $order->cashier_name" :back="route('orders.index')">
        <x-ui.button :href="route('orders.receipt', $order)" target="_blank" variant="secondary" icon="printer">Print receipt</x-ui.button>

        @if ($order->isCancellable())
            <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Are you sure you want to cancel this pending order?')">
                @csrf
                <x-ui.button type="submit" variant="danger" icon="x">Cancel order</x-ui.button>
            </form>
        @endif

        @if ($order->isRefundable())
            <x-ui.button variant="danger" icon="undo" x-on:click="$dispatch('open-modal', 'refund')">Refund or return</x-ui.button>
        @endif
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Order items" :padded="false">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-ui.product-thumb :product="$item->product" size="sm" />
                                    <div>
                                        <span class="block font-medium text-neutral-900">{{ $item->product?->name ?? 'Unknown item' }}</span>
                                        <span class="font-money text-xs text-neutral-500">{{ $item->product?->sku ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right"><x-ui.money :amount="$item->unit_price" /></td>
                            <td class="text-right font-money">
                                {{ $item->quantity }} <span class="text-xs text-neutral-500">{{ $item->unit?->abbreviation ?? $item->unit?->name ?? '' }}</span>
                                @if ($item->refunded_quantity > 0)
                                    <div class="text-xs text-red-600 font-normal">Refunded: {{ $item->refunded_quantity }}</div>
                                @endif
                            </td>
                            <td class="text-right font-money font-medium"><x-ui.money :amount="$item->line_total" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>

            <x-ui.card title="Details & Status">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 text-sm">
                    <div>
                        <p class="mb-1 text-xs text-neutral-500 font-medium">Order status</p>
                        <x-ui.status :status="$order->status" />
                    </div>
                    <div>
                        <p class="mb-1 text-xs text-neutral-500 font-medium">Payment status</p>
                        <x-ui.status :status="$order->payment_status" />
                    </div>
                    <div>
                        <p class="mb-1 text-xs text-neutral-500 font-medium">Payment method</p>
                        <span class="font-medium text-neutral-900">{{ strtoupper($order->payment_method) }}</span>
                    </div>
                    <div>
                        <p class="mb-1 text-xs text-neutral-500 font-medium">Customer</p>
                        <span class="font-medium text-neutral-900">{{ $order->customer_name }}</span>
                    </div>
                </div>
            </x-ui.card>
        </div>

        {{-- Receipt preview --}}
        <div>
            <div id="receipt" class="rounded-xl border border-line bg-white p-5 text-xs shadow-sm">
                <div class="text-center">
                    <p class="text-base font-bold tracking-tight text-neutral-950">{{ config('app.name', 'vPOS') }}</p>
                    <p class="text-neutral-500 mt-0.5">{{ $order->order_number }} · {{ $order->placed_at->format('d M Y, H:i') }}</p>
                </div>
                <div class="my-3 border-t border-dashed border-neutral-300"></div>
                @foreach ($order->items as $item)
                    <div class="mb-1.5 flex justify-between gap-2">
                        <span class="text-neutral-700">{{ $item->quantity }} × {{ $item->product?->name }}</span>
                        <span class="font-money font-medium"><x-ui.money :amount="$item->line_total" /></span>
                    </div>
                @endforeach
                <div class="my-3 border-t border-dashed border-neutral-300"></div>
                <div class="flex justify-between text-neutral-600 py-0.5">
                    <span>Subtotal</span>
                    <span class="font-money"><x-ui.money :amount="$order->subtotal" /></span>
                </div>
                @if ((float) $order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-700 py-0.5">
                        <span>Discount ({{ $order->discount_percent }}%)</span>
                        <span class="font-money">-<x-ui.money :amount="$order->discount_amount" /></span>
                    </div>
                @endif
                <div class="flex justify-between text-neutral-600 py-0.5">
                    <span>VAT ({{ $order->tax_rate }}%)</span>
                    <span class="font-money"><x-ui.money :amount="$order->tax_amount" /></span>
                </div>
                <div class="mt-2 pt-2 border-t border-neutral-200 flex justify-between text-sm font-bold text-neutral-950">
                    <span>Total</span>
                    <span class="font-money"><x-ui.money :amount="$order->total" /></span>
                </div>
                <div class="flex justify-end text-neutral-500 font-medium">
                    <x-ui.money :amount="$order->total" currency="khr" />
                </div>
                <p class="mt-4 text-center text-xs text-neutral-400">Thank you for your business!</p>
            </div>
        </div>
    </div>

    {{-- Refund / return modal --}}
    @php
        $refundLines = $order->items->map(fn ($item) => [
            'order_item_id' => $item->id,
            'name' => $item->product?->name ?? 'Item',
            'price' => (float) $item->unit_price,
            'max' => (float) $item->remaining_quantity,
            'qty' => 0,
            'restock' => true,
        ])->filter(fn ($l) => $l['max'] > 0)->values();
    @endphp

    @if ($refundLines->isNotEmpty())
        <x-ui.modal name="refund" title="Refund or return" description="Select items and quantities to refund. Stock is automatically returned when confirmed." size="lg">
            <form id="refund-form" method="POST" action="{{ route('orders.refund', $order) }}"
                  x-data="{
                      lines: @js($refundLines),
                      get total() {
                          return this.lines.reduce((sum, l) => sum + (l.price * (Number(l.qty) || 0)), 0);
                      }
                  }" class="space-y-5">
                @csrf
                <ul class="divide-y divide-neutral-100 border border-neutral-200 rounded-lg overflow-hidden">
                    <template x-for="(line, index) in lines" :key="index">
                        <li class="p-3 bg-white text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <input type="hidden" :name="'items[' + index + '][order_item_id]'" :value="line.order_item_id">
                                <div>
                                    <p class="font-medium text-neutral-900" x-text="line.name"></p>
                                    <p class="text-xs text-neutral-500" x-text="'Remaining eligible: ' + line.max + ' · $' + line.price.toFixed(2) + ' each'"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-neutral-500">Refund qty:</label>
                                    <input type="number" min="0" :max="line.max" x-model.number="line.qty" :name="'items[' + index + '][quantity]'"
                                           class="font-money h-9 w-20 rounded-lg border border-neutral-300 px-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                                </div>
                            </div>
                            <div class="mt-2 flex items-center justify-end">
                                <label class="flex items-center gap-1.5 text-xs text-neutral-600 cursor-pointer">
                                    <input type="checkbox" :name="'items[' + index + '][restock]'" value="1" x-model="line.restock" class="rounded border-neutral-300 text-neutral-900 focus:ring-neutral-900">
                                    <span>Restock returned inventory</span>
                                </label>
                            </div>
                        </li>
                    </template>
                </ul>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select label="Reason" name="reason" :options="['Customer changed mind' => 'Customer changed mind', 'Damaged item' => 'Damaged item', 'Wrong item' => 'Wrong item', 'Expired product' => 'Expired product', 'Other' => 'Other']" required />
                    <x-form.select label="Refund method" name="refund_method" :options="['cash' => 'Cash', 'original' => 'Original payment method', 'store_credit' => 'Store credit']" />
                </div>

                <div class="flex items-center justify-between rounded-lg bg-neutral-100 px-4 py-3">
                    <span class="text-sm font-medium text-neutral-700">Estimated refund total</span>
                    <span class="font-money text-lg font-bold text-neutral-900" x-text="'$' + total.toFixed(2)"></span>
                </div>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'refund')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="refund-form" variant="danger">Confirm refund</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</x-layouts.app>
