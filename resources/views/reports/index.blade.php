<x-layouts.app title="Reports">
    <x-ui.page-header title="Reports" subtitle="Understand how the shop is doing, then export it.">
        <x-ui.button variant="secondary" icon="printer" onclick="window.print()">Print / PDF</x-ui.button>
        <x-ui.button :href="route('reports.export', request()->all())" variant="secondary" icon="download">Export CSV</x-ui.button>
    </x-ui.page-header>

    {{-- Date filter, shared by every report --}}
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-4">
            <x-form.input label="From" class="datepicker" name="from" type="date" :value="$fromDate" />
            <x-form.input label="To" class="datepicker" name="to" type="date" :value="$toDate" />
            <x-form.select label="Quick range" name="range" :options="['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month']" :value="$currentRange" />
            <x-ui.button type="submit" icon="search">Apply filter</x-ui.button>
            @if (request()->hasAny(['from', 'to', 'range']))
                <x-ui.button :href="route('reports.index')" variant="ghost">Reset</x-ui.button>
            @endif
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
                        <div class="flex justify-between"><dt class="text-ink-muted">Orders</dt><dd class="font-money font-medium">{{ collect($reports['sales'])->sum(1) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-muted">Net sales</dt><dd class="font-medium"><x-ui.money :amount="collect($reports['sales'])->sum(2)" /></dd></div>
                        <div class="flex justify-between"><dt class="text-ink-muted">Discounts given</dt><dd class="font-medium"><x-ui.money :amount="collect($reports['sales'])->sum(3)" /></dd></div>
                    </dl>
                </x-ui.card>
            </div>
            <x-ui.card class="mt-6" :padded="false">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Orders</th>
                            <th class="text-right">Net sales</th>
                            <th class="text-right">Discounts</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($reports['sales'] as [$date, $orders, $net, $discount])
                        <tr>
                            <td>{{ $date }}</td>
                            <td class="text-right font-money">{{ $orders }}</td>
                            <td class="text-right"><x-ui.money :amount="$net" /></td>
                            <td class="text-right"><x-ui.money :amount="$discount" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <x-ui.empty-state icon="bar-chart" title="No sales data" description="No completed orders found for this period." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="revenue">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Period</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right">Cost of goods</th>
                            <th class="text-right">Gross profit</th>
                            <th class="text-right">Margin</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($reports['revenue'] as [$period, $revenue, $cost, $profit])
                        <tr>
                            <td>{{ $period }}</td>
                            <td class="text-right"><x-ui.money :amount="$revenue" /></td>
                            <td class="text-right"><x-ui.money :amount="$cost" /></td>
                            <td class="text-right"><x-ui.money :amount="$profit" /></td>
                            <td class="text-right font-money">{{ $revenue > 0 ? round(($profit / $revenue) * 100) : 0 }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <x-ui.empty-state icon="dollar-sign" title="No revenue data" description="No sales transactions recorded for this period." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="inventory">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Product</th>
                            <th class="text-right">In stock</th>
                            <th class="text-right">Cost</th>
                            <th class="text-right">Stock value</th>
                            <th>Status</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($products as $product)
                        <tr>
                            <td class="font-medium">{{ $product->name }}</td>
                            <td class="text-right font-money">{{ $product->stock }}</td>
                            <td class="text-right"><x-ui.money :amount="$product->cost" /></td>
                            <td class="text-right"><x-ui.money :amount="$product->cost * $product->stock" /></td>
                            <td><x-ui.status :status="$product->stock_level" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <x-ui.empty-state icon="layers" title="No products found" description="Add products to track inventory values." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="expenses">
            <x-ui.card title="Expenses by category">
                @php $maxExpense = (float) (collect($reports['expenses'])->max(1) ?? 0); @endphp
                @if (count($reports['expenses']) > 0 && $maxExpense > 0)
                    <ul class="space-y-4">
                        @foreach ($reports['expenses'] as [$category, $amount])
                            <li>
                                <div class="mb-1 flex justify-between text-sm">
                                    <span>{{ $category }}</span>
                                    <x-ui.money :amount="$amount" />
                                </div>
                                <div class="h-2 rounded-full bg-neutral-100">
                                    <div class="h-2 rounded-full bg-neutral-950" style="width: {{ $maxExpense > 0 ? round(($amount / $maxExpense) * 100) : 0 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-ui.empty-state icon="receipt" title="No expenses in this period" description="No expenses were recorded between the selected dates." />
                @endif
            </x-ui.card>
        </x-ui.tab-panel>
    </x-ui.tabs>
</x-layouts.app>
