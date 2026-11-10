<x-layouts.app title="Customers">
    <x-ui.page-header title="Customers" subtitle="The neighbours who shop with you.">
        <x-ui.button :href="route('customers.create')" icon="user-plus">Add customer</x-ui.button>
    </x-ui.page-header>

    <x-ui.card :padded="false">
        <div class="p-4"><x-ui.search class="max-w-sm" placeholder="Search by name or phone" /></div>
        <x-ui.table>
            <x-slot:head><tr><th>Customer</th><th>Phone</th><th class="text-right">Orders</th><th class="text-right">Total spent</th><th>Last visit</th><th>Status</th><th></th></tr></x-slot:head>
            @foreach ($customers as $customer)
                <tr>
                    <td><a href="{{ route('customers.show', $customer['id']) }}" class="flex items-center gap-3 font-medium hover:underline"><x-ui.avatar :name="$customer['name']" size="sm" /> {{ $customer['name'] }}</a></td>
                    <td class="font-money text-neutral-600">{{ $customer['phone'] }}</td>
                    <td class="text-right font-money">{{ $customer['orders_count'] }}</td>
                    <td class="text-right"><x-ui.money :amount="$customer['total_spent']" /></td>
                    <td class="text-neutral-600">{{ $customer['last_visit'] }}</td>
                    <td><x-ui.status :status="$customer['status']" /></td>
                    <td class="text-right"><x-ui.button :href="route('customers.edit', $customer['id'])" variant="ghost" size="sm" icon="pencil" aria-label="Edit" /></td>
                </tr>
            @endforeach
        </x-ui.table>
        <x-ui.pagination :to="$customers->count()" :total="$customers->count()" />
    </x-ui.card>
</x-layouts.app>
