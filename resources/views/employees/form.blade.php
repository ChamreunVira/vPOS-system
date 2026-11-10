@php $isEdit = (bool) $employee; @endphp
<x-layouts.app :title="$isEdit ? 'Edit employee' : 'New employee'">
    <form method="POST" action="{{ $isEdit ? route('employees.update', $employee['id']) : route('employees.store') }}" class="mx-auto max-w-3xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.page-header :title="$isEdit ? 'Edit employee' : 'New employee'" :subtitle="$isEdit ? $employee['name'] : 'Give a teammate access to vPOS.'" :back="route('employees.index')">
            <x-ui.button :href="route('employees.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">Save employee</x-ui.button>
        </x-ui.page-header>

        <div class="space-y-6">
            <x-ui.card title="Profile">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input class="sm:col-span-2" label="Full name" name="name" :value="$employee['name'] ?? null" required />
                    <x-form.input label="Email" name="email" type="email" :value="$employee['email'] ?? null" />
                    <x-form.input label="Phone" name="phone" type="tel" :value="$employee['phone'] ?? null" />
                </div>
            </x-ui.card>
            <x-ui.card title="Access">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select label="Role" name="role" :options="$roles" :value="$employee['role'] ?? 'Cashier'" hint="Roles control which modules this person can use." />
                    <x-form.input label="Checkout PIN" name="pin" type="password" inputmode="numeric" maxlength="6" hint="Used to unlock the POS." />
                    <x-form.input class="sm:col-span-2" label="Password" name="password" type="password" :hint="$isEdit ? 'Leave blank to keep the current password.' : null" />
                    <div class="sm:col-span-2"><x-form.toggle name="status" label="Active" hint="Inactive employees cannot sign in." :checked="($employee['status'] ?? 'active') === 'active'" /></div>
                </div>
            </x-ui.card>
        </div>
    </form>
</x-layouts.app>
