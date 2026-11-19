<x-layouts.app title="Payments">
    <x-ui.page-header title="Payments" subtitle="Money in, money out, and what should be in the drawer.">
        <x-ui.button variant="secondary" icon="arrow-up-right" @click="$dispatch('open-modal', 'cash-out')">Cash out</x-ui.button>
        <x-ui.button icon="arrow-down-left" @click="$dispatch('open-modal', 'cash-in')">Cash in</x-ui.button>
    </x-ui.page-header>

    <x-ui.tabs :tabs="['transactions' => 'Payment transactions', 'cash' => 'Cash in / out', 'summary' => 'Daily cash summary', 'methods' => 'Payment methods']">
        <x-ui.tab-panel name="transactions">
            <x-ui.card :padded="false">
                <form method="GET" action="{{ route('payments.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
                    <x-ui.search class="min-w-56 flex-1" placeholder="Search reference, order, or customer" />
                    <x-form.select label="Status" name="status" class="w-40" placeholder="All statuses" :options="['paid' => 'Paid', 'refunded' => 'Refunded', 'pending' => 'Pending', 'failed' => 'Failed']" :value="request('status')" />
                    <x-form.select label="Method" name="method" class="w-40" placeholder="All methods" :options="$paymentMethodOptions" :value="request('method')" />
                    <div class="w-40">
                        <x-form.input label="Date" name="date" type="date" :value="request('date')" />
                    </div>
                    <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
                    @if (request()->hasAny(['q', 'search', 'status', 'method', 'date']))
                        <x-ui.button :href="route('payments.index')" variant="ghost">Clear</x-ui.button>
                    @endif
                </form>

                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Time</th>
                            <th>Reference</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap text-neutral-600">
                                {{ ($payment->paid_at ?? $payment->created_at)?->format('M j, Y g:i A') ?? '-' }}
                            </td>
                            <td class="font-money text-neutral-600">{{ $payment->reference }}</td>
                            <td class="font-medium">
                                @if ($payment->order)
                                    <a href="{{ route('orders.show', $payment->order) }}" class="font-medium hover:underline">
                                        {{ $payment->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-neutral-400">-</span>
                                @endif
                            </td>
                            <td>{{ $payment->customer_name }}</td>
                            <td>{{ $payment->method_name }}</td>
                            <td><x-ui.status :status="$payment->status" /></td>
                            <td class="text-right font-money font-medium">
                                <x-ui.money :amount="$payment->amount" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <x-ui.empty-state icon="credit-card" title="No payment transactions found" description="Transactions will appear here when orders are paid." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>

                @if ($payments->hasPages())
                    <div class="border-t border-line px-5 py-3">{{ $payments->links() }}</div>
                @endif
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="cash">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Recorded by</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($cashMovements as $movement)
                        <tr>
                            <td class="whitespace-nowrap text-neutral-600">{{ $movement->created_at->format('M j, Y g:i A') }}</td>
                            <td>
                                <x-ui.badge :variant="$movement->type === 'in' ? 'soft' : 'outline'">
                                    {{ $movement->type === 'in' ? 'Cash in' : 'Cash out' }}
                                </x-ui.badge>
                            </td>
                            <td>{{ $movement->reason }}</td>
                            <td class="text-neutral-600">{{ $movement->user?->name ?? 'System' }}</td>
                            <td class="text-right font-money font-medium">
                                <x-ui.money :amount="$movement->type === 'in' ? $movement->amount : -$movement->amount" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <x-ui.empty-state icon="arrow-down-left" title="No cash movements recorded" description="Use Cash in or Cash out to record drawer adjustments." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>

                @if ($cashMovements->hasPages())
                    <div class="border-t border-line px-5 py-3">{{ $cashMovements->links() }}</div>
                @endif
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="summary">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card title="Today's takings" description="By payment method">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt>Cash sales</dt><dd class="font-medium"><x-ui.money :amount="$summary['cash_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>KHQR sales</dt><dd class="font-medium"><x-ui.money :amount="$summary['khqr_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>Card sales</dt><dd class="font-medium"><x-ui.money :amount="$summary['card_sales']" /></dd></div>
                        <div class="flex justify-between border-t border-line pt-3 font-semibold"><dt>Total</dt><dd><x-ui.money :amount="$summary['cash_sales'] + $summary['khqr_sales'] + $summary['card_sales']" /></dd></div>
                    </dl>
                </x-ui.card>
                <x-ui.card title="Cash drawer" description="Count the drawer and compare.">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt>Opening float</dt><dd class="font-medium"><x-ui.money :amount="$summary['opening_float']" /></dd></div>
                        <div class="flex justify-between"><dt>+ Cash sales</dt><dd class="font-medium"><x-ui.money :amount="$summary['cash_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>+ Cash in</dt><dd class="font-medium"><x-ui.money :amount="$summary['cash_in']" /></dd></div>
                        <div class="flex justify-between"><dt>− Cash out</dt><dd class="font-medium"><x-ui.money :amount="$summary['cash_out']" /></dd></div>
                        <div class="flex justify-between border-t border-line pt-3 font-semibold"><dt>Expected in drawer</dt><dd><x-ui.money :amount="$summary['expected_in_drawer']" /></dd></div>
                        <div class="flex justify-between"><dt>Counted</dt><dd class="font-medium"><x-ui.money :amount="$summary['counted']" /></dd></div>
                        <div class="flex justify-between border-t border-line pt-3 font-semibold text-neutral-700">
                            <dt>Difference</dt>
                            <dd class="{{ ($summary['counted'] - $summary['expected_in_drawer']) < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                <x-ui.money :amount="$summary['counted'] - $summary['expected_in_drawer']" />
                            </dd>
                        </div>
                    </dl>
                    <x-ui.button class="mt-5 w-full" variant="secondary" icon="printer" onclick="window.print()">Print summary</x-ui.button>
                </x-ui.card>
            </div>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="methods">
            <div class="grid gap-4 sm:grid-cols-3">
                @forelse ($paymentMethods as $method)
                    @php
                        $icon = match($method->code) {
                            'cash' => 'banknote',
                            'khqr' => 'qr-code',
                            'card' => 'credit-card',
                            default => 'layers',
                        };
                        $hint = match($method->code) {
                            'cash' => 'Count the drawer every shift.',
                            'khqr' => 'Bakong QR for any local banking app.',
                            'card' => 'Terminal card processing.',
                            default => 'Configured payment method.',
                        };
                    @endphp
                    <x-ui.card>
                        <div class="flex items-center justify-between">
                            <x-ui.icon :name="$icon" class="size-6 text-brand" />
                            <x-ui.badge :variant="$method->is_enable ? 'soft' : 'outline'">
                                {{ $method->is_enable ? 'Enabled' : 'Disabled' }}
                            </x-ui.badge>
                        </div>
                        <h3 class="mt-3 font-semibold">{{ $method->code === 'khqr' ? 'KHQR' : $method->name }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $hint }}</p>
                        <x-ui.button :href="route('settings.index')" variant="secondary" size="sm" class="mt-4">Configure</x-ui.button>
                    </x-ui.card>
                @empty
                    <div class="col-span-3">
                        <x-ui.empty-state icon="credit-card" title="No payment methods" description="Configure payment methods in system settings." />
                    </div>
                @endforelse
            </div>
        </x-ui.tab-panel>
    </x-ui.tabs>

    @foreach (['cash-in' => 'Cash in', 'cash-out' => 'Cash out'] as $name => $title)
        <x-ui.modal :name="$name" :title="$title" size="sm">
            <form id="{{ $name }}-form" method="POST" action="{{ route('payments.cash-movement') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="{{ $name === 'cash-in' ? 'in' : 'out' }}">
                <x-form.input label="Amount" name="amount" type="number" step="0.01" min="0.01" prefix="$" placeholder="0.00" required />
                <x-form.input label="Reason" name="reason" placeholder="e.g. {{ $name === 'cash-in' ? 'Top up change' : 'Pay delivery driver' }}" required />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" :data-modal="$name" @click="$dispatch('close-modal', $el.dataset.modal)">Cancel</x-ui.button>
                <x-ui.button type="submit" :form="$name.'-form'">Save</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
</x-layouts.app>
