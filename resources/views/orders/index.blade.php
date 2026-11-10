<x-layouts.app title="Orders">
    <x-ui.page-header title="Sales & orders" subtitle="Every sale, with its payment and refund status.">
        <x-ui.button variant="secondary" icon="download">Export</x-ui.button>
    </x-ui.page-header>

    <x-ui.card :padded="false">
        <div class="flex flex-wrap items-center gap-3 p-4">
            <x-ui.search class="min-w-56 flex-1" placeholder="Search by invoice or customer" />
            <x-form.select name="status" class="w-44" placeholder="All status" :options="['completed' => 'Completed', 'pending' => 'Pending', 'refunded' => 'Refunded', 'cancelled' => 'Cancelled']" />
            <x-form.select name="method" class="w-40" placeholder="All payments" :options="['cash' => 'Cash', 'khqr' => 'KHQR', 'card' => 'Card']" />
            <x-form.input name="date" type="date" />
        </div>
        <x-ui.table>
            <x-slot:head><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Payment</th><th>Payment status</th><th>Order status</th><th class="text-right">Total</th></tr></x-slot:head>
            @foreach ($orders as $order)
                <tr>
                    <td><a href="{{ route('orders.show', $order['id']) }}" class="font-medium hover:underline">{{ $order['number'] }}</a></td>
                    <td class="whitespace-nowrap text-neutral-600">{{ $order['placed_at']->format('M j, g:i A') }}</td>
                    <td>{{ $order['customer'] }}</td>
                    <td>{{ $order['method'] === 'khqr' ? 'KHQR' : ucfirst($order['method']) }}</td>
                    <td><x-ui.status :status="$order['payment_status']" /></td>
                    <td><x-ui.status :status="$order['status']" /></td>
                    <td class="text-right"><x-ui.money :amount="$order['total']" /></td>
                </tr>
            @endforeach
        </x-ui.table>
        <x-ui.pagination :to="$orders->count()" :total="$orders->count()" />
    </x-ui.card>
</x-layouts.app>
