<x-layouts.app title="Expenses">
    <div x-data="{
        tab: '{{ request('tab', 'expenses') }}',

        // Expense Form State
        expenseForm: {
            id: null,
            expense_category_id: '',
            amount: '',
            expense_date: '{{ now()->toDateString() }}',
            description: '',
            payment_method_id: ''
        },
        expenseEditMode: false,
        createExpense() {
            this.expenseEditMode = false;
            this.expenseForm = {
                id: null,
                expense_category_id: '',
                amount: '',
                expense_date: '{{ now()->toDateString() }}',
                description: '',
                payment_method_id: ''
            };
            $dispatch('open-modal', 'expense-form');
        },
        editExpense(expense) {
            this.expenseEditMode = true;
            this.expenseForm = {
                id: expense.id,
                expense_category_id: expense.expense_category_id,
                amount: expense.amount,
                expense_date: expense.expense_date,
                description: expense.description,
                payment_method_id: expense.payment_method_id ?? '',
            };
            $dispatch('open-modal', 'expense-form');
        },

        // Category Form State
        categoryForm: {
            id: null,
            name: '',
            description: '',
            status: true
        },
        categoryEditMode: false,
        createCategory() {
            this.categoryEditMode = false;
            this.categoryForm = {
                id: null,
                name: '',
                description: '',
                status: true
            };
            $dispatch('open-modal', 'category-form');
        },
        editCategory(category) {
            this.categoryEditMode = true;
            this.categoryForm = {
                id: category.id,
                name: category.name,
                description: category.description ?? '',
                status: category.status === 'active'
            };
            $dispatch('open-modal', 'category-form');
        }
    }">
        {{-- Page Header --}}
        <x-ui.page-header title="Expenses" subtitle="Rent, utilities, payroll, and operational spending.">
            <a href="{{ route('expenses.export') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50">
                <x-ui.icon name="download" class="size-4" /> Export CSV
            </a>
            <x-ui.button variant="secondary" icon="tag" @click="createCategory()">New category</x-ui.button>
            <x-ui.button icon="plus" @click="createExpense()">Add expense</x-ui.button>
        </x-ui.page-header>

        {{-- Stat Cards --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="This month" icon="calendar">
                <x-ui.money :amount="$thisMonthTotal" />
            </x-ui.stat-card>

            <x-ui.stat-card label="Largest category" icon="bar-chart" :hint="$largestCategory?->name ?? '—'">
                <x-ui.money :amount="$largestCategory?->total ?? 0" />
            </x-ui.stat-card>

            <x-ui.stat-card label="Recorded expenses" icon="receipt">
                {{ $expenses->total() }}
            </x-ui.stat-card>

            <x-ui.stat-card label="Expense categories" icon="tags" :hint="$activeCategoriesCount . ' active'">
                {{ $totalCategories }}
            </x-ui.stat-card>
        </div>

        <x-ui.flash />

        {{-- Tab Navigation --}}
        <div class="mb-5 flex gap-1 overflow-x-auto border-b border-line" role="tablist">
            <button
                type="button"
                role="tab"
                @click="tab = 'expenses'"
                :class="tab === 'expenses'
                    ? 'border-neutral-950 text-neutral-950 font-semibold'
                    : 'border-transparent text-neutral-500 hover:text-neutral-900'"
                class="-mb-px flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
            >
                {{-- <x-ui.icon name="receipt" class="size-4" /> --}}
                {{ __('Expenses list') }}
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-700">{{ $expenses->total() }}</span>
            </button>

            <button
                type="button"
                role="tab"
                @click="tab = 'categories'"
                :class="tab === 'categories'
                    ? 'border-neutral-950 text-neutral-950 font-semibold'
                    : 'border-transparent text-neutral-500 hover:text-neutral-900'"
                class="-mb-px flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
            >
                {{-- <x-ui.icon name="tags" class="size-4" /> --}}
                {{ __('Expense categories') }}
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs text-neutral-700">{{ $totalCategories }}</span>
            </button>
        </div>

        {{-- Tab 1: Expenses List --}}
        <div x-show="tab === 'expenses'" x-cloak role="tabpanel">
            <x-ui.card :padded="false">
                {{-- Filters --}}
                <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-center gap-3 p-4">
                    <input type="hidden" name="tab" value="expenses">
                    <x-ui.search class="min-w-56 flex-1" placeholder="Search description..." name="q" :value="request('q')" />
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
                    <x-ui.button type="submit" variant="secondary" size="sm">
                        <x-ui.icon name="filter" class="size-3.5" /> Filter
                    </x-ui.button>
                    @if (request()->hasAny(['q', 'category_id', 'date_from', 'date_to']))
                        <a href="{{ route('expenses.index', ['tab' => 'expenses']) }}" class="text-sm text-neutral-500 hover:text-neutral-700">Clear</a>
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
                            <td class="whitespace-nowrap text-neutral-600">
                                <span class="flex items-center gap-1.5">
                                    <x-ui.icon name="calendar" class="size-3.5 text-neutral-400" />
                                    {{ $expense->expense_date->format('M j, Y') }}
                                </span>
                            </td>
                            <td>
                                <x-ui.badge variant="soft" class="inline-flex items-center gap-1">
                                    <x-ui.icon name="tag" class="size-3" />
                                    {{ $expense->category->name ?? '—' }}
                                </x-ui.badge>
                            </td>
                            <td class="text-neutral-900 font-medium max-w-xs truncate">
                                {{ $expense->description ?? '—' }}
                            </td>
                            <td>
                                @php
                                    $methodCode = strtolower($expense->paymentMethod->code ?? ($expense->paymentMethod->name ?? 'cash'));
                                    $methodName = $expense->paymentMethod->name ?? 'Cash';
                                    $methodIcon = match ($methodCode) {
                                        'khqr' => 'qr-code',
                                        'card' => 'credit-card',
                                        default => 'banknote',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 text-xs text-neutral-600">
                                    <x-ui.icon :name="$methodIcon" class="size-3.5 text-neutral-500" />
                                    {{ strtoupper($methodName) === 'KHQR' ? 'KHQR' : ucfirst($methodName) }}
                                </span>
                            </td>
                            <td class="text-neutral-600">
                                <span class="flex items-center gap-1.5">
                                    <x-ui.icon name="user" class="size-3.5 text-neutral-400" />
                                    {{ $expense->creator->name ?? '—' }}
                                </span>
                            </td>
                            <td class="text-right font-money font-semibold text-neutral-900">
                                <x-ui.money :amount="$expense->amount" />
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        aria-label="Edit"
                                        type="button"
                                        @click="editExpense({
                                            id: {{ $expense->id }},
                                            expense_category_id: {{ $expense->expense_category_id }},
                                            amount: '{{ $expense->amount }}',
                                            expense_date: '{{ $expense->expense_date->format('Y-m-d') }}',
                                            description: @js($expense->description ?? ''),
                                            payment_method_id: '{{ $expense->payment_method_id ?? '' }}',
                                        })"
                                    />
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" aria-label="Delete" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <x-ui.empty-state
                                    icon="receipt"
                                    title="No expenses found"
                                    description="No expenses recorded yet, or no items match your search criteria."
                                >
                                    <x-ui.button icon="plus" size="sm" @click="createExpense()">Record first expense</x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>

                @if ($expenses->hasPages())
                    <div class="border-t border-line p-4">{{ $expenses->links() }}</div>
                @endif
            </x-ui.card>
        </div>

        {{-- Tab 2: Expense Categories --}}
        <div x-show="tab === 'categories'" x-cloak role="tabpanel">
            <x-ui.card :padded="false">
                {{-- Categories Filters --}}
                <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-line">
                    <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
                        <input type="hidden" name="tab" value="categories">
                        <x-ui.search class="min-w-56 flex-1" placeholder="Search categories..." name="cat_q" :value="request('cat_q')" />
                        <x-form.select
                            name="cat_status"
                            class="w-36"
                            placeholder="All status"
                            :options="['active' => 'Active', 'inactive' => 'Inactive']"
                            :value="request('cat_status')"
                        />
                        <x-ui.button type="submit" variant="secondary" size="sm">
                            <x-ui.icon name="filter" class="size-3.5" /> Filter
                        </x-ui.button>
                        @if (request()->hasAny(['cat_q', 'cat_status']))
                            <a href="{{ route('expenses.index', ['tab' => 'categories']) }}" class="text-sm text-neutral-500 hover:text-neutral-700">Clear</a>
                        @endif
                    </form>
                    <x-ui.button icon="plus" size="sm" @click="createCategory()">Add category</x-ui.button>
                </div>

                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-right">Expenses count</th>
                            <th class="text-right">Total spent</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </x-slot:head>

                    @forelse ($categoryItems as $cat)
                        <tr>
                            <td class="font-medium text-neutral-900">
                                <span class="flex items-center gap-2">
                                    <x-ui.icon name="tag" class="size-4 text-neutral-400" />
                                    {{ $cat->name }}
                                </span>
                            </td>
                            <td class="text-neutral-600 max-w-xs truncate">
                                {{ $cat->description ?? '—' }}
                            </td>
                            <td class="text-right font-money text-neutral-700">
                                <span class="inline-flex items-center gap-1 justify-end">
                                    <x-ui.icon name="receipt" class="size-3 text-neutral-400" />
                                    {{ $cat->expenses_count }}
                                </span>
                            </td>
                            <td class="text-right font-money font-semibold text-neutral-900">
                                <x-ui.money :amount="$cat->expenses_sum_amount ?? 0" />
                            </td>
                            <td>
                                <x-ui.status :status="$cat->status" />
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        aria-label="Edit"
                                        type="button"
                                        @click="editCategory({
                                            id: {{ $cat->id }},
                                            name: @js($cat->name),
                                            description: @js($cat->description ?? ''),
                                            status: @js($cat->status),
                                        })"
                                    />
                                    <form method="POST" action="{{ route('expense-categories.destroy', $cat) }}" onsubmit="return confirm('Delete this expense category?')">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" aria-label="Delete" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-ui.empty-state
                                    icon="tags"
                                    title="No expense categories found"
                                    description="Create categories like Rent, Utilities, or Maintenance to organize your expenses."
                                >
                                    <x-ui.button icon="plus" size="sm" @click="createCategory()">Add first category</x-ui.button>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        {{-- Modal 1: Add / Edit Expense --}}
        <x-ui.modal name="expense-form" title="Expense" description="Record operational spending that left the business.">
            <form id="expense-form" method="POST" class="space-y-4"
                :action="expenseEditMode ? `{{ url('expenses') }}/${expenseForm.id}` : '{{ route('expenses.store') }}'">
                @csrf
                <template x-if="expenseEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select
                        label="Category"
                        name="expense_category_id"
                        placeholder="Choose a category"
                        x-model="expenseForm.expense_category_id"
                        :options="$categories"
                        required
                    />
                    <x-form.input
                        label="Amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        prefix="$"
                        x-model="expenseForm.amount"
                        required
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input
                        label="Expense date"
                        name="expense_date"
                        type="date"
                        :max="now()->toDateString()"
                        x-model="expenseForm.expense_date"
                        required
                    />
                    <x-form.select
                        label="Paid with"
                        name="payment_method_id"
                        placeholder="Cash (default)"
                        x-model="expenseForm.payment_method_id"
                        :options="$paymentMethods"
                    />
                </div>

                <x-form.textarea label="Description" name="description" placeholder="e.g. Electric bill for October, Printer receipt paper" rows="2" x-model="expenseForm.description" />
            </form>

            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'expense-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="expense-form" x-text="expenseEditMode ? 'Update expense' : 'Save expense'"></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        {{-- Modal 2: Add / Edit Expense Category --}}
        <x-ui.modal name="category-form" title="Expense category" description="Classify your expenses into clear operational buckets.">
            <form id="category-form" method="POST" class="space-y-4"
                :action="categoryEditMode ? `{{ url('expense-categories') }}/${categoryForm.id}` : '{{ route('expense-categories.store') }}'">
                @csrf
                <template x-if="categoryEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <x-form.input
                    label="Category name"
                    name="name"
                    placeholder="e.g. Rent, Utilities, Packaging, Marketing"
                    x-model="categoryForm.name"
                    required
                />

                <x-form.textarea
                    label="Description"
                    name="description"
                    placeholder="Optional notes about what expenses belong here"
                    rows="3"
                    x-model="categoryForm.description"
                />

                <x-form.toggle
                    name="status"
                    label="Active status"
                    hint="Inactive categories will not appear in the dropdown when recording new expenses."
                    x-model="categoryForm.status"
                />
            </form>

            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'category-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="category-form" x-text="categoryEditMode ? 'Update category' : 'Save category'"></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
