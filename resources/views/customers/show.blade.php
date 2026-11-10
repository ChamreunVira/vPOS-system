<x-layouts.app :title="$customer['name']">
    <x-ui.page-header :title="$customer['name']" subtitle="Customer profile and purchase history" :back="route('customers.index')">
        <x-ui.button :href="route('customers.edit', $customer['id'])" variant="secondary" icon="pencil">Edit</x-ui.button>
        <x-ui.button :href="route('pos.index')" icon="cart">Start a sale</x-ui.button>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card>
            <div class="flex items-center gap-4"><x-ui.avatar :name="$customer['name']" size="lg" /><div><p class="font-semibold">{{ $customer['name'] }}</p><x-ui.status :status="$customer['status']" /></div></div>
            <dl class="mt-5 space-y-3 text-sm">
                <div><dt class="text-ink-muted">Phone</dt><dd class="font-money">{{ $customer['phone'] }}</dd></div>
                <div><dt class="text-ink-muted">Email</dt><dd>{{ $customer['email'] }}</dd></div>
                <div><dt class="text-ink-muted">Address</dt><dd>{{ $customer['address'] }}</dd></div>
                @if ($customer['notes'])<div><dt class="text-ink-muted">Notes</dt><dd>{{ $customer['notes'] }}</dd></div>@endif
            </dl>
        </x-ui.card>

        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-ui.stat-card label="Total spent"><x-ui.money :amount="$customer['total_spent']" /></x-ui.stat-card>
                <x-ui.stat-card label="Orders">{{ $customer['orders_count'] }}</x-ui.stat-card>
                <x-ui.stat-card label="Last visit"><span class="text-lg">{{ $customer['last_visit'] }}</span></x-ui.stat-card>
            </div>
            <x-ui.card title="Purchase history" :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Invoice</th><th>Date</th><th>Payment</th><th>Status</th><th class="text-right">Total</th></tr></x-slot:head>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order['id']) }}" class="font-medium hover:underline">{{ $order['number'] }}</a></td>
                            <td class="text-neutral-600">{{ $order['placed_at']->format('M j, Y') }}</td>
                            <td>{{ $order['method'] === 'khqr' ? 'KHQR' : ucfirst($order['method']) }}</td>
                            <td><x-ui.status :status="$order['status']" /></td>
                            <td class="text-right"><x-ui.money :amount="$order['total']" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
