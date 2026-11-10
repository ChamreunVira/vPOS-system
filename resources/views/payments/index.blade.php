<x-layouts.app title="Payments">
    <x-ui.page-header title="Payments" subtitle="Money in, money out, and what should be in the drawer.">
        <x-ui.button variant="secondary" icon="arrow-up-right" @click="$dispatch('open-modal', 'cash-out')">Cash out</x-ui.button>
        <x-ui.button icon="arrow-down-left" @click="$dispatch('open-modal', 'cash-in')">Cash in</x-ui.button>
    </x-ui.page-header>

    <x-ui.tabs :tabs="['transactions' => 'Payment transactions', 'cash' => 'Cash in / out', 'summary' => 'Daily cash summary', 'methods' => 'Payment methods']">
        <x-ui.tab-panel name="transactions">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Time</th><th>Reference</th><th>Order</th><th>Customer</th><th>Method</th><th>Status</th><th class="text-right">Amount</th></tr></x-slot:head>
                    @foreach ($payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap text-neutral-600">{{ $payment['at']->format('M j, g:i A') }}</td>
                            <td class="font-money text-neutral-600">{{ $payment['reference'] }}</td>
                            <td class="font-medium">{{ $payment['order'] }}</td>
                            <td>{{ $payment['customer'] }}</td>
                            <td>{{ $payment['method'] === 'khqr' ? 'KHQR' : ucfirst($payment['method']) }}</td>
                            <td><x-ui.status :status="$payment['status']" /></td>
                            <td class="text-right"><x-ui.money :amount="$payment['amount']" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="cash">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Time</th><th>Type</th><th>Reason</th><th>Recorded by</th><th class="text-right">Amount</th></tr></x-slot:head>
                    @foreach ($cashMovements as $movement)
                        <tr>
                            <td class="text-neutral-600">{{ $movement['time'] }}</td>
                            <td><x-ui.badge :variant="$movement['type'] === 'in' ? 'soft' : 'outline'">{{ $movement['type'] === 'in' ? 'Cash in' : 'Cash out' }}</x-ui.badge></td>
                            <td>{{ $movement['reason'] }}</td>
                            <td class="text-neutral-600">{{ $movement['user'] }}</td>
                            <td class="text-right"><x-ui.money :amount="$movement['type'] === 'in' ? $movement['amount'] : -$movement['amount']" /></td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="summary">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card title="Today's takings" description="By payment method">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt>Cash sales</dt><dd><x-ui.money :amount="$summary['cash_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>KHQR sales</dt><dd><x-ui.money :amount="$summary['khqr_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>Card sales</dt><dd><x-ui.money :amount="$summary['card_sales']" /></dd></div>
                        <div class="flex justify-between border-t border-line pt-3 font-semibold"><dt>Total</dt><dd><x-ui.money :amount="$summary['cash_sales'] + $summary['khqr_sales'] + $summary['card_sales']" /></dd></div>
                    </dl>
                </x-ui.card>
                <x-ui.card title="Cash drawer" description="Count the drawer and compare.">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt>Opening float</dt><dd><x-ui.money :amount="$summary['opening_float']" /></dd></div>
                        <div class="flex justify-between"><dt>+ Cash sales</dt><dd><x-ui.money :amount="$summary['cash_sales']" /></dd></div>
                        <div class="flex justify-between"><dt>+ Cash in</dt><dd><x-ui.money :amount="$summary['cash_in']" /></dd></div>
                        <div class="flex justify-between"><dt>− Cash out</dt><dd><x-ui.money :amount="$summary['cash_out']" /></dd></div>
                        <div class="flex justify-between border-t border-line pt-3 font-semibold"><dt>Expected in drawer</dt><dd><x-ui.money :amount="$summary['expected_in_drawer']" /></dd></div>
                        <div class="flex justify-between"><dt>Counted</dt><dd><x-ui.money :amount="$summary['counted']" /></dd></div>
                        <div class="flex justify-between"><dt>Difference</dt><dd><x-ui.money :amount="$summary['counted'] - $summary['expected_in_drawer']" /></dd></div>
                    </dl>
                    <x-ui.button class="mt-5 w-full" variant="secondary" icon="printer" onclick="window.print()">Print summary</x-ui.button>
                </x-ui.card>
            </div>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="methods">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([['Cash', 'banknote', 'Count the drawer every shift.'], ['KHQR', 'qr-code', 'Bakong QR for any local banking app.'], ['Card', 'credit-card', 'Terminal not connected yet.']] as [$label, $icon, $hint])
                    <x-ui.card>
                        <x-ui.icon :name="$icon" class="size-6" />
                        <h3 class="mt-3 font-semibold">{{ $label }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ $hint }}</p>
                        <x-ui.button :href="route('settings.index')" variant="secondary" size="sm" class="mt-4">Configure</x-ui.button>
                    </x-ui.card>
                @endforeach
            </div>
        </x-ui.tab-panel>
    </x-ui.tabs>

    @foreach (['cash-in' => 'Cash in', 'cash-out' => 'Cash out'] as $name => $title)
        <x-ui.modal :name="$name" :title="$title" size="sm">
            <form id="{{ $name }}-form" method="POST" action="{{ route('payments.cash-movement') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="{{ $name === 'cash-in' ? 'in' : 'out' }}">
                <x-form.input label="Amount" name="amount" type="number" step="0.01" min="0" prefix="$" />
                <x-form.input label="Reason" name="reason" placeholder="e.g. Pay delivery driver" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" :data-modal="$name" @click="$dispatch('close-modal', $el.dataset.modal)">Cancel</x-ui.button>
                <x-ui.button type="submit" :form="$name.'-form'">Save</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach
</x-layouts.app>
