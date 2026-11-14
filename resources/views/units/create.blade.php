@php $isEdit = (bool) $unit; @endphp
<x-layouts.app :title="$isEdit ? 'Edit unit' : 'New unit'">
    <form method="POST" action="{{ $isEdit ? route('units.update', $unit['id']) : route('units.store') }}" class="mx-auto max-w-3xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.page-header :title="$isEdit ? 'Edit unit' : 'New unit'" :subtitle="$isEdit ? $unit['name'] : 'Create a new unit for your business.'" :back="route('units.index')">
            <x-ui.button :href="route('units.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">Save unit</x-ui.button>
        </x-ui.page-header>

        <div class="space-y-6">
            <x-ui.card title="Profile">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input class="sm:col-span-2" label="Full name" name="name" :value="$unit['name'] ?? null" required />
                    <x-form.input label="Email" name="email" type="email" :value="$unit['email'] ?? null" required />
                    <x-form.input label="Phone" name="phone" type="tel" :value="$unit['phone'] ?? null" />
                </div>
            </x-ui.card>
            <x-ui.card title="Access">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select label="Role" name="role_id" :options="$roles" :value="$employee['role_id'] ?? null" placeholder="Choose a role" hint="Roles control which modules this person can use." required />
                    <x-form.input label="Password" name="password" type="password" :hint="$isEdit ? 'Leave blank to keep the current password.' : null" :required="! $isEdit" autocomplete="new-password" />
                    <x-form.input label="Confirm password" name="password_confirmation" type="password" :required="! $isEdit" autocomplete="new-password" />
                    <div class="sm:col-span-2"><x-form.toggle name="status" label="Active" hint="Inactive employees cannot sign in." :checked="($employee['status'] ?? 'active') === 'active'" /></div>
                </div>
            </x-ui.card>
        </div>
    </form>
</x-layouts.app>
