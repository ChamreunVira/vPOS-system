<x-layouts.app title="Reports">
    <x-ui.page-header title="Reports" subtitle="Understand how the shop is doing, then export it.">
        <x-ui.button variant="secondary" icon="file-text">Export PDF</x-ui.button>
        <x-ui.button variant="secondary" icon="download">Export Excel</x-ui.button>
    </x-ui.page-header>

    {{-- Date filter, shared by every report --}}
    <x-ui.card class="mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <x-form.input label="From" name="from" type="date" :value="now()->subDays(6)->toDateString()" />
            <x-form.input label="To" name="to" type="date" :value="now()->toDateString()" />
            <x-form.select label="Quick range" name="range" :options="['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month']" value="7d" />
            <x-ui.button type="submit" icon="search">Apply filter</x-ui.button>
        </form>
    </x-ui.card>

    <x-ui.tabs :tabs="['sales' => 'Sales', 'revenue' => 'Revenue & profit', 'inventory' => 'Inventory', 'expenses' => 'Expenses']">
        <x-ui.tab-panel name="sales">
            <div class="grid gap-6 lg:grid-cols-3">
                <x-ui.card title="Daily sales" class="lg:col-span-2">
                    <x-ui.bar-chart :data="collect($reports['sales'])->map(fn ($row) => ['label' => $row[0], 'value' => $row[2]])->all()" />
                </x-ui.card>
                <x-ui.card title="Summary">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-muted">Orders</dt><dd class="font-money">{{ collect($reports['sales'])->sum(1) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-muted">Net sales</dt><dd><x-ui.money :amount="collect($reports['sales'])->sum(2)" /></dd></div>
                        <div class="flex justify-between"><dt class="text-ink-muted">Discounts given</dt><dd><x-ui.money :amount="collect($reports['sales'])->sum(3)" /></dd></div>
                    </dl>
                </x-ui.card>
            </div>
            <x-ui.card class="mt-6" :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Date</th><th class="text-right">Orders</th><th class="text-right">Net sales</th><th class="text-right">Discounts</th></tr></x-slot:head>
                    @foreach ($reports['sales'] as [$date, $orders, $net, $discount])
                        <tr><td>{{ $date }}</td><td class="text-right font-money">{{ $orders }}</td><td class="text-right"><x-ui.money :amount="$net" /></td><td class="text-right"><x-ui.money :amount="$discount" /></td></tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="revenue">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Period</th><th class="text-right">Revenue</th><th class="text-right">Cost of goods</th><th class="text-right">Gross profit</th><th class="text-right">Margin</th></tr></x-slot:head>
                    @foreach ($reports['revenue'] as [$period, $revenue, $cost, $profit])
                        <tr><td>{{ $period }}</td><td class="text-right"><x-ui.money :amount="$revenue" /></td><td class="text-right"><x-ui.money :amount="$cost" /></td><td class="text-right"><x-ui.money :amount="$profit" /></td><td class="text-right font-money">{{ round($profit / $revenue * 100) }}%</td></tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="inventory">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Product</th><th class="text-right">In stock</th><th class="text-right">Cost</th><th class="text-right">Stock value</th><th>Status</th></tr></x-slot:head>
                    @foreach ($products as $product)
                        <tr>
                            <td class="font-medium">{{ $product['name'] }}</td>
                            <td class="text-right font-money">{{ $product['stock'] }}</td>
                            <td class="text-right"><x-ui.money :amount="$product['cost']" /></td>
                            <td class="text-right"><x-ui.money :amount="$product['cost'] * $product['stock']" /></td>
                            <td><x-ui.status :status="\App\Support\MockData::stockLevel($product)" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="expenses">
            <x-ui.card title="Expenses by category">
                @php $maxExpense = collect($reports['expenses'])->max(1); @endphp
                <ul class="space-y-4">
                    @foreach ($reports['expenses'] as [$category, $amount])
                        <li>
                            <div class="mb-1 flex justify-between text-sm"><span>{{ $category }}</span><x-ui.money :amount="$amount" /></div>
                            <div class="h-2 rounded-full bg-neutral-100"><div class="h-2 rounded-full bg-neutral-950" style="width: {{ round($amount / $maxExpense * 100) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </x-ui.tab-panel>
    </x-ui.tabs>
</x-layouts.app>
