<x-layouts.app title="Units">
    <div x-data="{
        units: {{ Js::from($units->values()) }},
        query: '',
        statusFilter: 'all',
        form: { id: null, name: '', abbreviation: '', type: 'Count', allow_decimal: false, status: 'active' },
        filteredUnits() {
            const query = this.query.trim().toLowerCase();

            return this.units.filter((unit) => {
                const matchesQuery = !query || [unit.name, unit.abbreviation, unit.type].some((value) => value.toLowerCase().includes(query));
                const matchesStatus = this.statusFilter === 'all' || unit.status === this.statusFilter;

                return matchesQuery && matchesStatus;
            });
        },
        create() {
            this.form = { id: null, name: '', abbreviation: '', type: 'Count', allow_decimal: false, status: 'active' };
            $dispatch('open-modal', 'unit-form');
        },
        edit(unit) {
            this.form = { ...unit, allow_decimal: Boolean(unit.allow_decimal) };
            $dispatch('open-modal', 'unit-form');
        },
    }">
        <x-ui.page-header title="Units" subtitle="Set the measurements and package types used across your products.">
            <x-ui.button icon="plus" @click="create()">Add unit</x-ui.button>
        </x-ui.page-header>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-ink-muted">Total units</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight">{{ $units->count() }}</p>
                    </div>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700"><x-ui.icon name="package" class="size-5" /></span>
                </div>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-ink-muted">Active units</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight">{{ $units->where('status', 'active')->count() }}</p>
                    </div>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><x-ui.icon name="check" class="size-5" /></span>
                </div>
            </x-ui.card>
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-ink-muted">Products using units</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight">{{ $units->sum('products_count') }}</p>
                    </div>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><x-ui.icon name="layers" class="size-5" /></span>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card :padded="false">
            <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
                    <input type="search" x-model="query" placeholder="Search units..." aria-label="Search units" class="h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                </div>
                <label class="flex items-center gap-2 text-sm text-neutral-600">
                    <span>Status</span>
                    <select x-model="statusFilter" class="h-10 rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
                        <option value="all">All units</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
            </div>

            <x-ui.table>
                <x-slot:head>
                    <tr><th>Unit</th><th>Type</th><th class="text-right">Products</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr>
                </x-slot:head>
                <template x-for="unit in filteredUnits()" :key="unit.id">
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-lg border border-line bg-neutral-50 text-xs font-semibold uppercase text-neutral-700" x-text="unit.abbreviation"></span>
                                <div>
                                    <p class="font-medium text-neutral-900" x-text="unit.name"></p>
                                    <p class="text-xs text-ink-muted">Symbol: <span x-text="unit.abbreviation"></span></p>
                                </div>
                            </div>
                        </td>
                        <td><span class="rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600" x-text="unit.type"></span></td>
                        <td class="text-right font-medium tabular-nums" x-text="unit.products_count"></td>
                        <td>
                            <template x-if="unit.status === 'active'"><x-ui.status status="active" /></template>
                            <template x-if="unit.status !== 'active'"><x-ui.status status="inactive" /></template>
                        </td>
                        <td class="text-right">
                            <x-ui.button variant="ghost" size="sm" icon="pencil" aria-label="Edit unit" ::aria-label="'Edit ' + unit.name" @click="edit(unit)" />
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredUnits().length === 0">
                    <td colspan="5" class="px-5 py-12 text-center">
                        <p class="font-medium text-neutral-900">No units found</p>
                        <p class="mt-1 text-sm text-ink-muted">Try another search or status filter.</p>
                    </td>
                </tr>
            </x-ui.table>
            <div class="flex items-center justify-between border-t border-line px-5 py-3 text-xs text-ink-muted">
                <span>Showing <span x-text="filteredUnits().length"></span> of {{ $units->count() }} units</span>
                <span>Unit symbols appear beside quantities on products and receipts.</span>
            </div>
        </x-ui.card>

        <x-ui.modal name="unit-form" title="Unit details" description="Add a clear name and symbol your team will recognize.">
            <form id="unit-form" method="POST" :action="form.id ? '{{ url('admin/units') }}/' + form.id : '{{ route('units.store') }}'" class="space-y-4">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input label="Unit name" name="name" placeholder="e.g. Piece" x-model="form.name" />
                        <x-form.input label="Symbol" name="abbreviation" placeholder="e.g. pc" x-model="form.abbreviation" />
                        <div class="sm:col-span-2">
                            <x-form.select label="Unit type" name="type" :options="['Count', 'Weight', 'Volume', 'Packaging', 'Length']" x-model="form.type" />
                        </div>
                    </div>
                    <div class="border-t border-line pt-4">
                        <x-form.toggle name="allow_decimal" label="Allow decimal quantities" hint="Enable fractional quantities for this unit." x-model="form.allow_decimal" />
                    </div>
                    <div class="border-t border-line pt-4">
                        <x-form.select label="Status" name="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" x-model="form.status" />
                    </div>
                </div>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'unit-form')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="unit-form">Save unit</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
