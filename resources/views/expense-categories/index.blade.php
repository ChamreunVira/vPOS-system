<x-layouts.app title="Expense categories">
    <div x-data="{
        form: { id: null, name: '', description: '', status: true },
        editMode: false,
        create() {
            this.editMode = false;
            this.form = { id: null, name: '', description: '', status: true };
            $dispatch('open-modal', 'expense-category-form');
        },
        edit(category) {
            this.editMode = true;
            this.form = {
                id: category.id,
                name: category.name,
                description: category.description ?? '',
                status: category.status === 'active',
            };
            $dispatch('open-modal', 'expense-category-form');
        },
    }">
        <x-ui.page-header title="Expense categories" subtitle="Organize and classify where your shop spends money."
            :back="route('expenses.index')">
            <x-ui.button :href="route('expenses.index')" variant="secondary" icon="banknote">View expenses</x-ui.button>
            <x-ui.button icon="plus" @click="create()">Add category</x-ui.button>
        </x-ui.page-header>

        {{-- Stat cards --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card label="Total categories">
                {{ $totalCategories }}
            </x-ui.stat-card>
            <x-ui.stat-card label="Active categories">
                {{ $activeCategories }}
            </x-ui.stat-card>
            <x-ui.stat-card label="Total spent across all">
                <x-ui.money :amount="$totalSpent" />
            </x-ui.stat-card>
        </div>

        <x-ui.flash />

        <x-ui.card :padded="false">
            {{-- Filters --}}
            <form method="GET" action="{{ route('expense-categories.index') }}"
                class="flex flex-wrap items-center gap-3 p-4">
                <x-ui.search class="min-w-56 flex-1" placeholder="Search by name or description" name="q"
                    :value="request('q')" />
                <x-form.select name="status" class="w-40" placeholder="All status" :options="['active' => 'Active', 'inactive' => 'Inactive']"
                    :value="request('status')" />
                <x-ui.button type="submit" variant="secondary" size="sm">Filter</x-ui.button>
                @if (request()->hasAny(['q', 'status']))
                    <a href="{{ route('expense-categories.index') }}"
                        class="text-sm text-neutral-500 hover:text-neutral-700">Clear</a>
                @endif
            </form>

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

                @forelse ($categories as $category)
                    <tr>
                        <td class="font-medium text-neutral-900">
                            {{ $category->name }}
                        </td>
                        <td class="text-neutral-600 max-w-xs truncate">
                            {{ $category->description ?? '—' }}
                        </td>
                        <td class="text-right font-money text-neutral-700">
                            {{ $category->expenses_count }}
                        </td>
                        <td class="text-right font-money">
                            <x-ui.money :amount="$category->expenses_sum_amount ?? 0" />
                        </td>
                        <td>
                            <x-ui.status :status="$category->status" />
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-ui.button variant="edit" size="sm" icon="pencil" aria-label="Edit"
                                    type="button"
                                    @click="edit({
                                        id: {{ $category->id }},
                                        name: @js($category->name),
                                        description: @js($category->description ?? ''),
                                        status: @js($category->status),
                                    })" />
                                <form method="POST" action="{{ route('expense-categories.destroy', $category) }}"
                                    onsubmit="return confirm('Delete this expense category?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="delete" size="sm" icon="trash"
                                        aria-label="Delete" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <x-ui.empty-state icon="tags" title="No expense categories found"
                                description="Create categories like Utilities, Rent, or Inventory to categorize expenses." />
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>

            @if ($categories->hasPages())
                <div class="border-t border-line p-4">{{ $categories->links() }}</div>
            @endif
        </x-ui.card>

        {{-- Add / Edit Modal --}}
        <x-ui.modal name="expense-category-form" title="Expense category"
            description="Classify your expenses into clear buckets.">
            <form id="expense-category-form" method="POST" class="space-y-4"
                :action="editMode ? `{{ url('expense-categories') }}/${form.id}` : '{{ route('expense-categories.store') }}'">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <x-form.input label="Category name" name="name" placeholder="e.g. Rent, Utilities, Packaging"
                    x-model="form.name" required />

                <x-form.textarea label="Description" name="description"
                    placeholder="Optional notes about what goes into this category" rows="3"
                    x-model="form.description" />

                <x-form.toggle name="status" label="Active"
                    hint="Inactive categories will not appear when recording new expenses." x-model="form.status" />
            </form>

            <x-slot:footer>
                <x-ui.button variant="secondary"
                    @click="$dispatch('close-modal', 'expense-category-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="expense-category-form"
                    x-text="editMode ? 'Update category' : 'Save category'"></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
