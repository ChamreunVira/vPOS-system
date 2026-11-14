@php $isEdit = (bool) $employee; @endphp
<x-layouts.app :title="$isEdit ? 'Edit employee' : 'New employee'">
    <form method="POST" action="{{ $isEdit ? route('employees.update', $employee->id) : route('employees.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-ui.page-header :title="$isEdit ? 'Edit employee' : 'New employee'" :subtitle="$isEdit ? $employee->name : 'Give a teammate access to vPOS.'" :back="route('employees.index')">
            <x-ui.button :href="route('employees.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" icon="check">Save employee</x-ui.button>
        </x-ui.page-header>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Employee details">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input class="sm:col-span-2" label="Full name" name="name" :value="$employee?->name" required />
                        <x-form.input label="Email" name="email" type="email" :value="$employee?->email" required />
                        <x-form.input label="Phone" name="phone" type="tel" :value="$employee?->phone" />
                    </div>
                </x-ui.card>

                <x-ui.card title="Sign-in access" description="Choose the role and sign-in credentials for this employee.">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.select class="sm:col-span-2" label="Role" name="role_id" :options="$roles" :value="$employee?->role_id" placeholder="Choose a role" hint="Roles control which modules this person can use." required />
                        <x-form.input label="Password" name="password" type="password" :hint="$isEdit ? 'Leave blank to keep the current password.' : null" :required="! $isEdit" autocomplete="new-password" />
                        <x-form.input label="Confirm password" name="password_confirmation" type="password" :required="! $isEdit" autocomplete="new-password" />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Availability">
                    <div class="space-y-2">
                        <x-form.toggle name="status" label="Active" hint="Inactive employees cannot sign in." :checked="($employee?->status ?? 'active') === 'active'" />
                        @error('status')<p class="text-xs font-medium text-neutral-950">{{ $message }}</p>@enderror
                    </div>
                </x-ui.card>
            </div>
        </div>
    </form>
</x-layouts.app>
