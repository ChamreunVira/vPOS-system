<x-layouts.app title="Expenses">
    <div x-data="{
        form: { id: null, category: '', description: '', amount: '', date: '{{ now()->toDateString() }}' },
        create() { this.form = { id: null, category: '', description: '', amount: '', date: '{{ now()->toDateString() }}' }; $dispatch('open-modal', 'expense-form') },
    }">
        <x-ui.page-header title="Expenses" subtitle="Rent, bills, wages and everything else the shop pays for.">
            <x-ui.button variant="secondary" icon="download">Export</x-ui.button>
            <x-ui.button icon="plus" @click="create()">Add expense</x-ui.button>
        </x-ui.page-header>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="This month"><x-ui.money :amount="$expenses->sum('amount')" /></x-ui.stat-card>
            <x-ui.stat-card label="Largest category" hint="Rent"><x-ui.money :amount="450" /></x-ui.stat-card>
            <x-ui.stat-card label="Entries">{{ $expenses->count() }}</x-ui.stat-card>
        </div>

        <x-ui.card :padded="false">
            <div class="flex flex-wrap items-center gap-3 p-4">
                <x-ui.search class="min-w-56 flex-1" placeholder="Search description" />
                <x-form.select name="category" class="w-48" placeholder="All categories" :options="$categories" />
            </div>
            <x-ui.table>
                <x-slot:head><tr><th>Date</th><th>Category</th><th>Description</th><th>Paid with</th><th>Recorded by</th><th class="text-right">Amount</th><th></th></tr></x-slot:head>
                @foreach ($expenses as $expense)
                    <tr>
                        <td class="whitespace-nowrap text-neutral-600">{{ $expense['date'] }}</td>
                        <td><x-ui.badge>{{ $expense['category'] }}</x-ui.badge></td>
                        <td>{{ $expense['description'] }}</td>
                        <td>{{ $expense['method'] === 'khqr' ? 'KHQR' : ucfirst($expense['method']) }}</td>
                        <td class="text-neutral-600">{{ $expense['created_by'] }}</td>
                        <td class="text-right"><x-ui.money :amount="$expense['amount']" /></td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('expenses.destroy', $expense['id']) }}" onsubmit="return confirm('Delete this expense?')">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" aria-label="Delete" />
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.modal name="expense-form" title="Add expense" description="Record money that left the business.">
            <form id="expense-form" method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select label="Category" name="category" placeholder="Choose a category" x-model="form.category" :options="$categories" />
                    <x-form.input label="Amount" name="amount" type="number" step="0.01" min="0" prefix="$" x-model="form.amount" />
                </div>
                <x-form.input label="Date" name="date" type="date" x-model="form.date" />
                <x-form.textarea label="Description" name="description" rows="2" x-model="form.description" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'expense-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="expense-form">Save expense</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
