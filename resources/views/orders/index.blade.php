<x-layouts.app title="Orders">
    <x-ui.page-header title="Sales & orders" subtitle="Every sale, with its payment and refund status.">
        <x-ui.button :href="route('orders.export', request()->only(['q', 'status', 'method', 'date']))" variant="secondary" icon="download">Export</x-ui.button>
    </x-ui.page-header>

    <x-ui.card :padded="false">
        <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
            <x-ui.search class="min-w-56 flex-1" placeholder="Search by invoice or customer" />
            <x-form.select label="Status" name="status" class="w-44" placeholder="All status" :options="['completed' => 'Completed', 'partially_refunded' => 'Partially Refunded', 'refunded' => 'Refunded', 'pending' => 'Pending', 'cancelled' => 'Cancelled']" :value="request('status')" />
            <x-form.select label="Payment" name="method" class="w-40" placeholder="All payments" :options="$paymentMethods" :value="request('method')" />
            <div class="w-40">
                <x-form.input label="Date" name="date" type="date" :value="request('date')" />
            </div>
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            @if (request()->hasAny(['q', 'status', 'method', 'date']))
                <x-ui.button :href="route('orders.index')" variant="ghost">Clear</x-ui.button>
            @endif
        </form>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Cashier</th>
                    <th>Payment</th>
                    <th>Payment status</th>
                    <th>Order status</th>
                    <th class="text-right">Total</th>
                    <th class="text-right"><span class="sr-only">Actions</span></th>
                </tr>
            </x-slot:head>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('orders.show', $order) }}" class="font-medium hover:underline">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap text-neutral-600">{{ $order->placed_at->format('M j, Y g:i A') }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td class="text-neutral-600 text-xs">{{ $order->cashier->name }}</td>
                    <td>{{ strtoupper($order->payment_method) }}</td>
                    <td><x-ui.status :status="$order->payment_status" /></td>
                    <td><x-ui.status :status="$order->status" /></td>
                    <td class="text-right font-money font-medium"><x-ui.money :amount="$order->total" /></td>
                    <td class="text-right">
                        <x-ui.button :href="route('orders.show', $order)" variant="view" size="sm" icon="arrow-up-right" aria-label="View order">View</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-5 py-12 text-center">
                        <x-ui.empty-state icon="layers" title="No orders found" description="Try clearing filters or ring up sales in POS." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
        <div class="border-t border-line px-5 py-3">{{ $orders->links() }}</div>
    </x-ui.card>
</x-layouts.app>
