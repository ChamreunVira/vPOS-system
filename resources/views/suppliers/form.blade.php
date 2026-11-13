@php $isEdit = (bool) $supplier; @endphp
<x-layouts.app :title="$isEdit ? 'Edit supplier' : 'New supplier'">
    <x-ui.page-header :title="$isEdit ? 'Edit supplier' : 'New supplier'" :subtitle="$isEdit ? $supplier->name : 'Add the contact details used for stock purchases.'" :back="route('suppliers.index')">
        <x-ui.button :href="route('suppliers.index')" variant="secondary">Cancel</x-ui.button>
        <x-ui.button type="submit" form="supplier-form" icon="check">Save supplier</x-ui.button>
    </x-ui.page-header>

    <form id="supplier-form" method="POST" action="{{ $isEdit ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="mx-auto max-w-3xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.card title="Contact information">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input class="sm:col-span-2" label="Supplier name" name="name" :value="$supplier?->name" required />
                <x-form.input label="Phone" name="phone" type="tel" :value="$supplier?->phone" required />
                <x-form.input label="Email" name="email" type="email" :value="$supplier?->email" required />
                <div class="sm:col-span-2"><x-form.textarea label="Address" name="address" rows="3" :value="$supplier?->address" required /></div>
                <div class="sm:col-span-2"><x-form.toggle name="status" label="Active supplier" hint="Inactive suppliers remain in purchase history but are no longer available for new orders." :checked="($supplier?->status ?? 'active') === 'active'" /></div>
            </div>
        </x-ui.card>
    </form>
</x-layouts.app>
