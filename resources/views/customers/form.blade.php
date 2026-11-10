@php $isEdit = (bool) $customer; @endphp
<x-layouts.app :title="$isEdit ? 'Edit customer' : 'New customer'">
    <form method="POST" action="{{ $isEdit ? route('customers.update', $customer['id']) : route('customers.store') }}" class="mx-auto max-w-3xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.page-header :title="$isEdit ? 'Edit customer' : 'New customer'" :subtitle="$isEdit ? $customer['name'] : 'Save contact details to speed up future sales.'" :back="route('customers.index')">
            <x-ui.button :href="route('customers.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">Save customer</x-ui.button>
        </x-ui.page-header>

        <x-ui.card title="Contact information">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input class="sm:col-span-2" label="Full name" name="name" :value="$customer['name'] ?? null" required />
                <x-form.input label="Phone" name="phone" type="tel" placeholder="+855 12 345 678" :value="$customer['phone'] ?? null" />
                <x-form.input label="Email" name="email" type="email" :value="$customer['email'] ?? null" />
                <div class="sm:col-span-2"><x-form.textarea label="Address" name="address" rows="2" :value="$customer['address'] ?? null" /></div>
                <div class="sm:col-span-2"><x-form.textarea label="Notes" name="notes" rows="2" hint="Only staff can see this." :value="$customer['notes'] ?? null" /></div>
                <div class="sm:col-span-2"><x-form.toggle name="status" label="Active customer" :checked="($customer['status'] ?? 'active') === 'active'" /></div>
            </div>
        </x-ui.card>
    </form>
</x-layouts.app>
