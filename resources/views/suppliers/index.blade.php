<x-layouts.app title="Suppliers">
    <x-ui.flash />
    <x-ui.page-header title="Suppliers" subtitle="Keep supplier contacts and purchasing history together.">
        <x-ui.button :href="route('suppliers.create')" icon="plus">Add supplier</x-ui.button>
    </x-ui.page-header>

    <x-ui.card :padded="false">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex flex-wrap items-center gap-3 p-4">
            <x-ui.search name="search" placeholder="Search name, phone or email" class="max-w-sm flex-1" />
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
            @if ($search !== '')
                <x-ui.button :href="route('suppliers.index')" variant="ghost">Clear</x-ui.button>
            @endif
        </form>

        <x-ui.table>
            <x-slot:head><tr><th>Supplier</th><th>Phone</th><th>Email</th><th>Address</th><th class="text-right">Purchase orders</th><th>Status</th><th></th></tr></x-slot:head>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td class="font-medium">{{ $supplier->name }}</td>
                    <td class="font-money text-neutral-600">{{ $supplier->phone }}</td>
                    <td class="text-neutral-600">{{ $supplier->email }}</td>
                    <td class="max-w-xs truncate text-neutral-600">{{ $supplier->address }}</td>
                    <td class="text-right font-money">{{ $supplier->purchase_orders_count }}</td>
                    <td><x-ui.status :status="$supplier->status" /></td>
                    <td class="text-right">
                        <div class="flex justify-end gap-1">
                            <x-ui.button :href="route('suppliers.edit', $supplier)" variant="ghost" size="sm" icon="pencil" aria-label="Edit {{ $supplier->name }}" />
                            @if ($supplier->status === 'active')
                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Deactivate this supplier?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="ghost" size="sm" icon="x" aria-label="Deactivate {{ $supplier->name }}" />
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-ink-muted">No suppliers found. Add a supplier to start tracking purchases.</td></tr>
            @endforelse
        </x-ui.table>
        <div class="border-t border-line px-5 py-3">{{ $suppliers->links() }}</div>
    </x-ui.card>
</x-layouts.app>
