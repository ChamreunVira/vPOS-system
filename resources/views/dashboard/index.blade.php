<x-layouts.app title="Dashboard">
    <x-ui.page-header title="Good day, Rithy." :subtitle="now()->format('l, F j').' · Here is how '.config('vpos.store').' is doing.'">
        <x-ui.button :href="route('pos.index')" icon="cart" size="lg">Start a sale</x-ui.button>
    </x-ui.page-header>

    {{-- Key numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Today's sales" icon="cart" :delta="$stats['todays_sales_delta']"><x-ui.money :amount="$stats['todays_sales']" /></x-ui.stat-card>
        <x-ui.stat-card label="Total revenue" icon="trending-up" :delta="$stats['total_revenue_delta']"><x-ui.money :amount="$stats['total_revenue']" /></x-ui.stat-card>
        <x-ui.stat-card label="Estimated profit" icon="wallet" :delta="$stats['estimated_profit_delta']"><x-ui.money :amount="$stats['estimated_profit']" /></x-ui.stat-card>
        <x-ui.stat-card label="Total expenses" icon="banknote" :delta="$stats['total_expenses_delta']"><x-ui.money :amount="$stats['total_expenses']" /></x-ui.stat-card>
        <x-ui.stat-card label="Total products" icon="package" :hint="'Across '.$activeCategories.' active categories'">{{ $totalProducts }}</x-ui.stat-card>
        <x-ui.stat-card label="Low-stock products" icon="alert" hint="At or below the minimum level">{{ $lowStockProducts->count() }}</x-ui.stat-card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Sales chart with range tabs --}}
        <x-ui.card title="Sales" description="Net sales compared with the previous period" class="xl:col-span-2">
            <div x-data="{ range: 'week' }">
                <div class="mb-4 inline-flex rounded-lg bg-neutral-100 p-1 text-sm">
                    @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month'] as $key => $label)
                        <button type="button" @click="range = '{{ $key }}'" :class="range === '{{ $key }}' ? 'bg-white shadow-sm' : 'text-neutral-500'" class="rounded-md px-3 py-1 font-medium">{{ $label }}</button>
                    @endforeach
                </div>
                @foreach ($charts as $key => $series)
                    <div x-show="range === '{{ $key }}'" x-cloak><x-ui.bar-chart :data="$series" /></div>
                @endforeach
                <div class="mt-4 flex items-center gap-5 text-xs text-ink-muted">
                    <span class="flex items-center gap-2"><span class="size-2.5 rounded-sm bg-neutral-950"></span> Current</span>
                    <span class="flex items-center gap-2"><span class="size-2.5 rounded-sm bg-neutral-200"></span> Previous</span>
                </div>
            </div>
        </x-ui.card>

        {{-- Best sellers --}}
        <x-ui.card title="Best-selling products" description="Units sold this month">
            <ol class="space-y-4">
                @foreach ($bestSellers as $product)
                    <li class="flex items-center gap-3">
                        <x-ui.product-thumb :product="$product" size="sm" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('products.show', $product['id']) }}" class="block truncate text-sm font-medium hover:underline">{{ $product['name'] }}</a>
                            <p class="text-xs text-ink-muted">{{ $product['category'] }}</p>
                        </div>
                        <span class="font-money text-sm">{{ number_format($product['sold']) }}</span>
                    </li>
                @endforeach
            </ol>
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Recent transactions" class="xl:col-span-2" :padded="false">
            <x-slot:actions><x-ui.button :href="route('orders.index')" variant="ghost" size="sm">View all</x-ui.button></x-slot:actions>
            <x-ui.table>
                <x-slot:head><tr><th>Sale</th><th>Customer</th><th>Payment</th><th>Status</th><th class="text-right">Total</th></tr></x-slot:head>
                @foreach ($recentOrders as $order)
                    <tr>
                        <td><a href="{{ route('orders.show', $order['id']) }}" class="font-medium hover:underline">{{ $order['number'] }}</a><p class="text-xs text-ink-muted">{{ $order['placed_at']->format('g:i A') }}</p></td>
                        <td>{{ $order['customer'] }}</td>
                        <td class="capitalize">{{ $order['method'] === 'khqr' ? 'KHQR' : $order['method'] }}</td>
                        <td><x-ui.status :status="$order['status']" /></td>
                        <td class="text-right"><x-ui.money :amount="$order['total']" /></td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Needs attention" description="Restock these soon">
            <ul class="space-y-3">
                @foreach ($lowStockProducts->take(5) as $product)
                    <li class="flex items-center gap-3">
                        <x-ui.product-thumb :product="$product" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $product['name'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $product['stock'] }} left · reorder at {{ $product['min_stock'] }}</p>
                        </div>
                        <x-ui.status :status="\App\Support\MockData::stockLevel($product)" />
                    </li>
                @endforeach
            </ul>
            <x-ui.button :href="route('inventory.index')" variant="secondary" class="mt-5 w-full">Review inventory</x-ui.button>
        </x-ui.card>
    </div>
</x-layouts.app>
