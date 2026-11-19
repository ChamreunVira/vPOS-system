<x-layouts.app :title="$purchase->purchase_number">
    <x-ui.flash />

    <x-ui.page-header :title="'Purchase order ' . $purchase->purchase_number"
                      :subtitle="($purchase->supplier?->name ?? 'Unknown supplier') . ' · ' . ($purchase->ordered_at?->format('F j, Y') ?? 'No date') . ' · Created by ' . ($purchase->creator?->name ?? 'System')"
                      :back="route('purchases.index')">
        <div class="flex flex-wrap items-center gap-2">
            @if ($purchase->canReceive())
                <x-ui.button variant="primary" icon="check" x-data x-on:click="$dispatch('open-modal', 'receive-modal')">
                    Receive order
                </x-ui.button>
            @endif

            @if ($purchase->canEdit())
                <x-ui.button :href="route('purchases.edit', $purchase)" variant="secondary" icon="pencil">
                    Edit
                </x-ui.button>
            @endif

            @if ($purchase->canCancel())
                <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" onsubmit="return confirm('Are you sure you want to cancel this purchase order?')">
                    @csrf
                    <x-ui.button type="submit" variant="danger" icon="x">
                        Cancel order
                    </x-ui.button>
                </form>
            @endif

            @if ($purchase->canDelete())
                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Are you sure you want to delete this purchase order?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="ghost" icon="trash" class="text-neutral-500 hover:text-red-600">
                        Delete
                    </x-ui.button>
                </form>
            @endif
        </div>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Left: Items and Stock Movements --}}
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Purchased items" :padded="false">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Unit cost</th>
                            <th class="text-right">Ordered qty</th>
                            @if ($purchase->isReceived())
                                <th class="text-right">Received qty</th>
                            @endif
                            <th class="text-right">Line total</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-ui.product-thumb :product="$item->product" size="sm" />
                                    <div>
                                        <a href="{{ $item->product ? route('products.show', $item->product) : '#' }}" class="font-medium text-neutral-900 hover:underline">
                                            {{ $item->product?->name ?? 'Unknown item' }}
                                        </a>
                                        <div class="text-xs text-neutral-500">
                                            SKU: {{ $item->product?->sku ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right font-money">
                                <x-ui.money :amount="$item->unit_cost" />
                            </td>
                            <td class="text-right font-money">
                                {{ (float) $item->quantity }}
                                <span class="text-xs text-neutral-500">{{ $item->unit?->abbreviation ?? $item->unit?->name ?? $item->product?->unit?->abbreviation ?? 'pc' }}</span>
                            </td>
                            @if ($purchase->isReceived())
                                <td class="text-right font-money font-medium text-emerald-600">
                                    {{ (float) $item->received_quantity }}
                                </td>
                            @endif
                            <td class="text-right font-money font-medium">
                                <x-ui.money :amount="$item->line_total" />
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="border-t border-line bg-neutral-50/50 p-4 sm:flex sm:items-center sm:justify-between">
                    <span class="text-sm font-medium text-neutral-600">
                        Total items: <span class="font-bold text-neutral-900">{{ (float) $purchase->items->sum('quantity') }}</span>
                    </span>
                    <div class="mt-2 text-right sm:mt-0">
                        <span class="text-xs text-neutral-500">Grand total</span>
                        <div class="text-xl font-bold font-money text-neutral-950">
                            <x-ui.money :amount="$purchase->total" />
                        </div>
                    </div>
                </div>
            </x-ui.card>

            @if ($purchase->isReceived() && $purchase->stockMovements->isNotEmpty())
                <x-ui.card title="Inventory stock movements" subtitle="Recorded when this purchase order was received." :padded="false">
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th>Product</th>
                                <th>Type</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Balance after</th>
                                <th>Logged by</th>
                                <th>Date</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($purchase->stockMovements as $movement)
                            <tr>
                                <td class="font-medium text-neutral-900">
                                    {{ $movement->product?->name ?? 'Unknown product' }}
                                </td>
                                <td>
                                    <x-ui.badge variant="soft">{{ ucfirst($movement->type) }}</x-ui.badge>
                                </td>
                                <td class="text-right font-money font-semibold text-emerald-600">
                                    +{{ (float) $movement->quantity }}
                                </td>
                                <td class="text-right font-money">
                                    {{ (float) $movement->balance }}
                                </td>
                                <td class="text-xs text-neutral-600">
                                    {{ $movement->user?->name ?? 'System' }}
                                </td>
                                <td class="whitespace-nowrap text-xs text-neutral-500">
                                    {{ $movement->created_at->format('M j, Y g:i A') }}
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </x-ui.card>
            @endif
        </div>

        {{-- Right: Status and Details --}}
        <div class="space-y-6 lg:col-span-1">
            <x-ui.card title="Order status">
                <div class="space-y-4 text-sm">
                    <div>
                        <span class="text-xs font-medium text-neutral-500">Status</span>
                        <div class="mt-1">
                            <x-ui.status :status="$purchase->status" />
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-neutral-500">Total amount</span>
                        <p class="mt-0.5 text-lg font-bold font-money text-neutral-950">
                            <x-ui.money :amount="$purchase->total" />
                        </p>
                    </div>

                    <div class="border-t border-line pt-3">
                        <span class="text-xs font-medium text-neutral-500">Ordered date</span>
                        <p class="mt-0.5 font-medium text-neutral-900">
                            {{ $purchase->ordered_at?->format('F j, Y · g:i A') ?? '—' }}
                        </p>
                    </div>

                    @if ($purchase->received_at)
                        <div class="border-t border-line pt-3">
                            <span class="text-xs font-medium text-neutral-500">Received date</span>
                            <p class="mt-0.5 font-medium text-emerald-700">
                                {{ $purchase->received_at->format('F j, Y · g:i A') }}
                            </p>
                        </div>
                    @endif

                    <div class="border-t border-line pt-3">
                        <span class="text-xs font-medium text-neutral-500">Created by</span>
                        <p class="mt-0.5 font-medium text-neutral-900">
                            {{ $purchase->creator?->name ?? 'System' }}
                        </p>
                    </div>

                    @if ($purchase->notes)
                        <div class="border-t border-line pt-3">
                            <span class="text-xs font-medium text-neutral-500">Notes</span>
                            <p class="mt-0.5 whitespace-pre-wrap text-sm text-neutral-700">
                                {{ $purchase->notes }}
                            </p>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card title="Supplier details">
                @if ($purchase->supplier)
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-xs font-medium text-neutral-500">Supplier</span>
                            <p class="font-medium text-neutral-900">{{ $purchase->supplier->name }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-neutral-500">Phone</span>
                            <p class="font-money text-neutral-700">{{ $purchase->supplier->phone }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-neutral-500">Email</span>
                            <p class="text-neutral-700">{{ $purchase->supplier->email }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-medium text-neutral-500">Address</span>
                            <p class="text-neutral-700">{{ $purchase->supplier->address }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-neutral-500">No supplier linked.</p>
                @endif
            </x-ui.card>
        </div>
    </div>

    @if ($purchase->canReceive())
        <x-ui.modal name="receive-modal" title="Receive purchase order" description="Confirm receiving goods into inventory.">
            <form id="receive-form" method="POST" action="{{ route('purchases.receive', $purchase) }}" class="space-y-4">
                @csrf
                <p class="text-sm text-neutral-700">
                    Receiving order <strong>{{ $purchase->purchase_number }}</strong> will automatically increase inventory stock for all {{ $purchase->items->count() }} item(s) and log the stock movements.
                </p>

                <div class="rounded-lg border border-line bg-neutral-50 p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="update_cost" value="0">
                        <input type="checkbox" name="update_cost" value="1" checked
                               class="mt-0.5 rounded text-neutral-900 focus:ring-neutral-900">
                        <div>
                            <span class="block text-sm font-medium text-neutral-900">Update product cost</span>
                            <span class="block text-xs text-neutral-500">
                                Automatically update the product cost in your catalogue to match the unit cost on this purchase order.
                            </span>
                        </div>
                    </label>
                </div>
            </form>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', 'receive-modal')">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" form="receive-form" variant="primary" icon="check">
                    Confirm & receive
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</x-layouts.app>
