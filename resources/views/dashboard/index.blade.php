<x-layouts.app title="Dashboard">
    <x-ui.page-header :title="'Good day, ' . (auth()->user()?->name ? explode(' ', auth()->user()->name)[0] : 'there') . '.'" :subtitle="now()->locale(app()->getLocale())->translatedFormat('l, F j') . ' · ' . __('Here is how :store is doing.', ['store' => config('vpos.store', config('app.name', 'vPOS'))])">
        <x-ui.button :href="route('pos.index')" icon="cart" size="lg">Start a sale</x-ui.button>
    </x-ui.page-header>

    {{-- Key numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Today's sales" icon="cart" :delta="$stats['todays_sales_delta']"><x-ui.money :amount="$stats['todays_sales']" /></x-ui.stat-card>
        <x-ui.stat-card label="Total revenue" icon="trending-up" :delta="$stats['total_revenue_delta']"><x-ui.money :amount="$stats['total_revenue']" /></x-ui.stat-card>
        <x-ui.stat-card label="Estimated profit" icon="wallet" :delta="$stats['estimated_profit_delta']"><x-ui.money :amount="$stats['estimated_profit']" /></x-ui.stat-card>
        <x-ui.stat-card label="Total expenses" icon="banknote" :delta="$stats['total_expenses_delta']"><x-ui.money :amount="$stats['total_expenses']" /></x-ui.stat-card>
        <x-ui.stat-card label="Total products" icon="package" :hint="'Across ' . $activeCategories . ' active categories'">{{ $totalProducts }}</x-ui.stat-card>
        <x-ui.stat-card label="Low-stock products" icon="alert" hint="At or below the minimum level">{{ $lowStockProducts->count() }}</x-ui.stat-card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Sales chart with range tabs --}}
        <x-ui.card :padded="false" class="xl:col-span-2">
            <div x-data="{ range: 'week' }">
                {{-- Card header with inline tab switcher --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">Sales</p>
                        <p class="text-xs text-neutral-500">Net sales vs. the previous period</p>
                    </div>
                    <div class="flex rounded-lg border border-neutral-200 bg-neutral-50 p-0.5">
                        @foreach (['today' => 'Today', 'week' => 'Week', 'month' => 'Month'] as $key => $label)
                            <button
                                type="button"
                                @click="range = '{{ $key }}'"
                                :class="range === '{{ $key }}'
                                    ? 'bg-white shadow-sm text-neutral-900 font-semibold'
                                    : 'text-neutral-500 hover:text-neutral-700'"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-all"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Chart panels --}}
                <div class="px-2 pb-4">
                    @foreach ($charts as $key => $series)
                        <div x-show="range === '{{ $key }}'" x-cloak>
                            <x-ui.area-chart :data="$series" height="h-64" />
                        </div>
                    @endforeach
                </div>

                {{-- Legend --}}
                <div class="flex items-center gap-5 border-t border-neutral-100 px-5 py-3">
                    <span class="flex items-center gap-2 text-xs text-neutral-500">
                        <span class="inline-block h-0.5 w-5 rounded-full bg-neutral-900"></span>
                        Current period
                    </span>
                    <span class="flex items-center gap-2 text-xs text-neutral-500">
                        <span class="inline-block h-0.5 w-5 rounded-full bg-neutral-300"></span>
                        Previous period
                    </span>
                </div>
            </div>
        </x-ui.card>

        {{-- Best sellers --}}
        <x-ui.card title="Best-selling products" description="Units sold this month">
            <ol class="space-y-4">
                @forelse ($bestSellers as $product)
                    <li class="flex items-center gap-3">
                        <x-ui.product-thumb :product="$product" size="sm" />
                        <div class="min-w-0 flex-1">
                            @if (!empty($product['id']))
                                <a href="{{ route('products.show', $product['id']) }}" class="block truncate text-sm font-medium hover:underline">{{ $product['name'] }}</a>
                            @else
                                <span class="block truncate text-sm font-medium">{{ $product['name'] }}</span>
                            @endif
                            <p class="text-xs text-ink-muted">{{ $product['category'] }}</p>
                        </div>
                        <span class="font-money shrink-0 whitespace-nowrap text-sm font-semibold">{{ number_format($product['sold']) }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-xs text-neutral-500">No products sold yet.</li>
                @endforelse
            </ol>
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-ui.card title="Recent transactions" class="xl:col-span-2" :padded="false">
            <x-slot:actions><x-ui.button :href="route('orders.index')" variant="ghost" size="sm">View all</x-ui.button></x-slot:actions>
            <x-ui.table class="[&_table]:min-w-[600px]">
                <x-slot:head><tr><th>Sale</th><th>Customer</th><th>Payment</th><th>Status</th><th class="text-right">Total</th></tr></x-slot:head>
                @forelse ($recentOrders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('orders.show', $order['id']) }}" class="font-medium hover:underline">{{ $order['number'] }}</a>
                            <p class="text-xs text-ink-muted">{{ $order['placed_at']?->format('g:i A') ?? '—' }}</p>
                        </td>
                        <td>{{ $order->customer_name }}</td>
                        <td class="capitalize">{{ $order['method'] === 'khqr' ? 'KHQR' : ucfirst($order['method'] ?? 'cash') }}</td>
                        <td><x-ui.status :status="$order['status']" /></td>
                        <td class="text-right font-medium"><x-ui.money :amount="$order['total']" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-xs text-neutral-500">No transactions recorded yet.</td>
                    </tr>
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Needs attention" description="Restock these soon">
            <ul class="space-y-3">
                @forelse ($lowStockProducts->take(5) as $product)
                    <li class="flex items-center gap-3">
                        <x-ui.product-thumb :product="$product" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $product['name'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $product['stock'] }} left · reorder at {{ $product['min_stock'] }}</p>
                        </div>
                        <x-ui.status :status="$product['stock_level'] ?? 'low_stock'" />
                    </li>
                @empty
                    <li class="py-6 text-center text-xs text-neutral-500">All products have sufficient stock.</li>
                @endforelse
            </ul>
            <x-ui.button :href="route('inventory.index')" variant="secondary" class="mt-5 w-full">Review inventory</x-ui.button>
        </x-ui.card>
    </div>
</x-layouts.app>
