<x-layouts.app title="Purchases">
    <x-ui.flash />

    <x-ui.page-header title="Purchase orders" subtitle="Manage stock orders from suppliers, update costs, and receive inventory.">
        <x-ui.button :href="route('purchases.export', request()->only(['q', 'status', 'supplier_id', 'date']))" variant="secondary" icon="download">Export</x-ui.button>
        <x-ui.button :href="route('purchases.create')" icon="plus">New purchase order</x-ui.button>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.stat-card label="Total spend" icon="banknote">
            <x-ui.money :amount="$stats['total_spend']" />
        </x-ui.stat-card>
        <x-ui.stat-card label="Received orders" icon="package">
            {{ $stats['received_count'] }}
        </x-ui.stat-card>
        <x-ui.stat-card label="Pending orders" icon="clock">
            {{ $stats['pending_count'] }}
        </x-ui.stat-card>
        <x-ui.stat-card label="Total orders" icon="file-text">
            {{ $stats['total_count'] }}
        </x-ui.stat-card>
    </div>

    <x-ui.card :padded="false">
        <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
            <x-ui.search name="q" :value="request('q')" class="min-w-56 flex-1" placeholder="Search PO #, supplier or notes" />

            <div class="w-40">
                <x-form.select label="Status" name="status" placeholder="All statuses"
                    :options="['pending' => 'Pending', 'received' => 'Received', 'cancelled' => 'Cancelled']"
                    :value="request('status')" />
            </div>

            <div class="w-48">
                <x-form.select label="Supplier" name="supplier_id" placeholder="All suppliers"
                    :options="$suppliers"
                    :value="request('supplier_id')" />
            </div>

            <div class="w-40">
                <x-form.input label="Ordered date" name="date" type="date" :value="request('date')" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            @if (request()->hasAny(['q', 'status', 'supplier_id', 'date']))
                <x-ui.button :href="route('purchases.index')" variant="ghost">Clear</x-ui.button>
            @endif
        </form>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Ordered Date</th>
                    <th>Received Date</th>
                    <th class="text-right">Items</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th class="text-right"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @forelse ($purchases as $purchase)
                <tr>
                    <td>
                        <a href="{{ route('purchases.show', $purchase) }}" class="font-medium text-neutral-900 hover:underline">
                            {{ $purchase->purchase_number }}
                        </a>
                        @if ($purchase->notes)
                            <p class="max-w-xs truncate text-xs text-neutral-500">{{ $purchase->notes }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="font-medium">{{ $purchase->supplier?->name ?? 'Unknown supplier' }}</span>
                    </td>
                    <td class="whitespace-nowrap text-neutral-600">
                        {{ $purchase->ordered_at?->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap text-neutral-600">
                        {{ $purchase->received_at?->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="text-right font-money">
                        {{ (int) $purchase->items->sum('quantity') }}
                    </td>
                    <td class="text-right font-money font-medium">
                        <x-ui.money :amount="$purchase->total" />
                    </td>
                    <td>
                        <x-ui.status :status="$purchase->status" />
                    </td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <x-ui.button :href="route('purchases.show', $purchase)" variant="ghost" size="sm" icon="eye" aria-label="View {{ $purchase->purchase_number }}" />

                            @if ($purchase->canEdit())
                                <x-ui.button :href="route('purchases.edit', $purchase)" variant="ghost" size="sm" icon="pencil" aria-label="Edit {{ $purchase->purchase_number }}" />
                            @endif

                            @if ($purchase->canCancel())
                                <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" onsubmit="return confirm('Are you sure you want to cancel purchase order {{ $purchase->purchase_number }}?')">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="x" class="text-red-600 hover:text-red-700 hover:bg-red-50" aria-label="Cancel {{ $purchase->purchase_number }}" />
                                </form>
                            @endif

                            @if ($purchase->canDelete())
                                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Are you sure you want to delete purchase order {{ $purchase->purchase_number }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" class="text-neutral-500 hover:text-red-600 hover:bg-red-50" aria-label="Delete {{ $purchase->purchase_number }}" />
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center">
                        <x-ui.empty-state icon="file-text" title="No purchase orders found" description="Create a purchase order to record new items and increase your stock." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div class="border-t border-line px-5 py-3">{{ $purchases->links() }}</div>
    </x-ui.card>
</x-layouts.app>
