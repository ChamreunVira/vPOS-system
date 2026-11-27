<x-layouts.app title="Categories">
    {{-- One Alpine scope shared by the table buttons and the modal form (create + edit use the same modal). --}}
    <div x-data="{
        form: { id: null, name: '', description: '', status: true },
        create() { this.form = { id: null, name: '', description: '', status: true }; $dispatch('open-modal', 'category-form') },
        edit(category) { this.form = { id: category.id, name: category.name, description: category.description, status: category.status === 'active' }; $dispatch('open-modal', 'category-form') },
    }">
        <x-ui.page-header title="Categories" subtitle="Group products so they are easy to find at the counter.">
            <x-ui.button icon="plus" @click="create()">Add category</x-ui.button>
        </x-ui.page-header>

        <x-ui.card :padded="false">

             <form method="GET" action="{{ route('categories.index') }}" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
                <input type="hidden" name="tab" value="overview">
                <x-ui.search type='search' class="min-w-56 flex-1" placeholder="Search by name, description" />
                {{-- <x-form.select label="Category" name="category_id" class="w-48" placeholder="All categories" :options="$categories" :value="request('category_id')" /> --}}
                {{-- <x-form.select label="Stock level" name="stock_status" class="w-40" placeholder="All stock levels" :options="['in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock']" :value="request('stock_status')" /> --}}
                <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
                @if (request()->hasAny(['q', 'category_id', 'stock_status']))
                    <x-ui.button :href="route('inventory.index')" variant="ghost">Clear</x-ui.button>
                @endif
            </form>

            <x-ui.table>
                <x-slot:head><tr><th>Name</th><th>Description</th><th class="text-right">Products</th><th>Status</th><th></th></tr></x-slot:head>
                @foreach ($categories as $category)
                    <tr>
                        <td class="font-medium">{{ $category['name'] }}</td>
                        <td class="text-neutral-600">{{ $category['description'] }}</td>
                        <td class="text-right font-money">{{ $category['products_count'] }}</td>
                        <td><x-ui.status :status="$category['status']" /></td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <span class="contents" @click="edit({{ Js::from($category) }})"><x-ui.button variant="edit" size="sm" icon="pencil" aria-label="Edit">Edit</x-ui.button></span>
                                <form method="POST" action="{{ route('categories.destroy', $category['id']) }}" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="delete" size="sm" icon="trash" aria-label="Delete">Delete</x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.modal name="category-form" title="Category" description="Name it the way your staff say it out loud.">
            <form id="category-form" method="POST" :action="form.id ? '{{ url('admin/categories') }}/' + form.id : '{{ route('categories.store') }}'" class="space-y-4">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <x-form.input label="Name" name="name" x-model="form.name" required />
                <x-form.textarea label="Description" name="description" x-model="form.description" />
                <x-form.toggle name="status" label="Active" hint="Inactive categories are hidden from the POS." x-model="form.status" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'category-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="category-form">Save category</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
