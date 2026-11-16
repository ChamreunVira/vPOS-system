<x-layouts.app title="Expenses">
    <div x-data="{
        form: { id: null, expense_category_id: '', amount: '', expense_date: '{{ now()->toDateString() }}', description: '', payment_method_id: '' },
        editMode: false,
        create() {
            this.editMode = false;
            this.form = { id: null, expense_category_id: '', amount: '', expense_date: '{{ now()->toDateString() }}', description: '', payment_method_id: '' };
            $dispatch('open-modal', 'expense-form');
        },
        edit(expense) {
            this.editMode = true;
            this.form = {
                id: expense.id,
                expense_category_id: expense.expense_category_id,
                amount: expense.amount,
                expense_date: expense.expense_date,
                description: expense.description,
                payment_method_id: expense.payment_method_id ?? '',
            };
            $dispatch('open-modal', 'expense-form');
        },
    }">
        <x-ui.page-header title="Expenses" subtitle="Rent, bills, wages and everything else the shop pays for.">
            <x-ui.button :href="route('expense-categories.index')" variant="secondary" icon="tags">Categories</x-ui.button>
            <a href="{{ route('expenses.export') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50">
                <x-ui.icon name="download" class="size-4" /> Export
            </a>
            <x-ui.button icon="plus" @click="create()">Add expense</x-ui.button>
        </x-ui.page-header>

        {{-- Stats --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="This month">
                <x-ui.money :amount="$thisMonthTotal" />
            </x-ui.stat-card>

            <x-ui.stat-card
                label="Largest category"
                :hint="$largestCategory?->name ?? '—'"
            >
                <x-ui.money :amount="$largestCategory?->total ?? 0" />
            </x-ui.stat-card>

            <x-ui.stat-card label="Entries">
                {{ $expenses->total() }}
            </x-ui.stat-card>
        </div>

        <x-ui.flash />

        <x-ui.card :padded="false">
            {{-- Filters --}}
            <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-center gap-3 p-4">
                <x-ui.search class="min-w-56 flex-1" placeholder="Search description" name="q" :value="request('q')" />
                <x-form.select
                    name="category_id"
                    class="w-48"
                    placeholder="All categories"
                    :options="$categories"
                    :value="request('category_id')"
                />
                <x-form.input
                    type="date"
                    name="date_from"
                    label=""
                    placeholder="From"
                    class="w-36"
                    :value="request('date_from')"
                />
                <x-form.input
                    type="date"
                    name="date_to"
                    label=""
                    placeholder="To"
                    class="w-36"
                    :value="request('date_to')"
                />
                <x-ui.button type="submit" variant="secondary" size="sm">Filter</x-ui.button>
                @if (request()->hasAny(['q', 'category_id', 'date_from', 'date_to']))
                    <a href="{{ route('expenses.index') }}" class="text-sm text-neutral-500 hover:text-neutral-700">Clear</a>
                @endif
            </form>

            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Paid with</th>
                        <th>Recorded by</th>
                        <th class="text-right">Amount</th>
                        <th></th>
                    </tr>
                </x-slot:head>

                @forelse ($expenses as $expense)
                    <tr>
                        <td class="whitespace-nowrap text-neutral-600">{{ $expense->expense_date->format('M j, Y') }}</td>
                        <td><x-ui.badge>{{ $expense->category->name ?? '—' }}</x-ui.badge></td>
                        <td>{{ $expense->description ?? '—' }}</td>
                        <td>{{ $expense->paymentMethod ? (strtoupper($expense->paymentMethod->name) === 'KHQR' ? 'KHQR' : ucfirst($expense->paymentMethod->name)) : 'Cash' }}</td>
                        <td class="text-neutral-600">{{ $expense->creator->name ?? '—' }}</td>
                        <td class="text-right"><x-ui.money :amount="$expense->amount" /></td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-ui.button
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    aria-label="Edit"
                                    type="button"
                                    @click="edit({
                                        id: {{ $expense->id }},
                                        expense_category_id: {{ $expense->expense_category_id }},
                                        amount: '{{ $expense->amount }}',
                                        expense_date: '{{ $expense->expense_date->format('Y-m-d') }}',
                                        description: '{{ addslashes($expense->description ?? '') }}',
                                        payment_method_id: '{{ $expense->payment_method_id ?? '' }}',
                                    })"
                                />
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" aria-label="Delete" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-neutral-500">
                            No expenses found.
                            @if (request()->hasAny(['q', 'category_id', 'date_from', 'date_to']))
                                <a href="{{ route('expenses.index') }}" class="ml-1 underline">Clear filters</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>

            @if ($expenses->hasPages())
                <div class="p-4">{{ $expenses->links() }}</div>
            @endif
        </x-ui.card>

        {{-- Add / Edit Modal --}}
        <x-ui.modal name="expense-form" title="Expense" description="Record money that left the business.">

            <form id="expense-form" method="POST" class="space-y-4"
                :action="editMode ? `/expenses/${form.id}` : '{{ route('expenses.store') }}'">
                @csrf
                <span x-show="editMode" x-cloak>
                    <input type="hidden" name="_method" value="PUT">
                </span>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select
                        label="Category"
                        name="expense_category_id"
                        placeholder="Choose a category"
                        x-model="form.expense_category_id"
                        :options="$categories"
                    />
                    <x-form.input
                        label="Amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        prefix="$"
                        x-model="form.amount"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input
                        label="Date"
                        name="expense_date"
                        type="date"
                        :max="now()->toDateString()"
                        x-model="form.expense_date"
                    />
                    <x-form.select
                        label="Paid with"
                        name="payment_method_id"
                        placeholder="Cash (default)"
                        x-model="form.payment_method_id"
                        :options="$paymentMethods"
                    />
                </div>

                <x-form.textarea label="Description" name="description" rows="2" x-model="form.description" />
            </form>

            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'expense-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="expense-form" x-text="editMode ? 'Update expense' : 'Save expense'"></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
