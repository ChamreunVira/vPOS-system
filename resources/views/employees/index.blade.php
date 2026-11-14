<x-layouts.app title="Employees">
    <x-ui.page-header title="Employees" subtitle="Who can sign in, and what they can do.">
        <x-ui.button :href="route('employees.create')" icon="plus">Add employee</x-ui.button>
    </x-ui.page-header>

    <x-ui.tabs :tabs="['employees' => 'Employees', 'roles' => 'Roles & permissions']">
        <x-ui.tab-panel name="employees">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Employee</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr></x-slot:head>
                    @forelse ($employees as $employee)
                        <tr>
                            <td><span class="flex items-center gap-3"><x-ui.avatar :name="$employee->name" size="sm" /><span><span class="block font-medium">{{ $employee->name }}</span><span class="text-xs text-ink-muted">{{ $employee->email }}</span></span></span></td>
                            <td class="font-money text-neutral-600">{{ $employee->phone ?: 'N/A' }}</td>
                            <td><x-ui.badge :variant="$employee->role?->name === 'admin' ? 'solid' : 'soft'">{{ $employee->role?->name ? __(ucfirst($employee->role->name)) : __('Unassigned') }}</x-ui.badge></td>
                            <td><x-ui.status :status="$employee->status" /></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <x-ui.button :href="route('employees.edit', $employee->id)" variant="ghost" size="sm" icon="pencil" aria-label="Edit" />
                                    <form method="POST" action="{{ route('employees.destroy', $employee->id) }}" onsubmit="return confirm('Deactivate this employee? They will no longer be able to sign in.')">
                                        @csrf @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm" icon="pause" aria-label="Deactivate" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-ink-muted">No employees have been added yet.</td></tr>
                    @endforelse
                </x-ui.table>
                {{ $employees->links() }}
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="roles">
            <form method="POST" action="{{ route('roles.permissions.update') }}">
                @csrf
                @method('PUT')
                <x-ui.card :padded="false" title="Permission matrix" description="Choose the actions each role can perform in every module.">
                    <x-slot:actions><x-ui.button type="submit" icon="check" size="sm">Save permissions</x-ui.button></x-slot:actions>
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th>Module</th>
                                @foreach ($roles as $role)
                                    <th class="text-center">{{ __(ucfirst($role->name)) }}<span class="block text-[11px] font-normal text-neutral-400">{{ __('View') }} · {{ __('Create') }} · {{ __('Edit') }} · {{ __('Delete') }}</span></th>
                                @endforeach
                            </tr>
                        </x-slot:head>
                        @forelse ($permissionsByModule as $module => $permissions)
                            <tr>
                                <td class="font-medium">{{ __($module) }}</td>
                                @foreach ($roles as $role)
                                    <td>
                                        <div class="flex justify-center gap-3">
                                            @php
                                                $previousPermissions = old('permissions.'.$role->id);
                                                $selectedPermissionIds = is_array($previousPermissions)
                                                    ? array_map('intval', $previousPermissions)
                                                    : $role->permissions->modelKeys();
                                            @endphp
                                            @foreach ($permissions as $permission)
                                                @php $actionLabel = $permission->action === 'update' ? 'edit' : $permission->action; @endphp
                                                <label title="{{ __(ucfirst($actionLabel)) }} {{ __($module) }}" class="inline-flex cursor-pointer items-center">
                                                    <input type="checkbox" name="permissions[{{ $role->id }}][]" value="{{ $permission->id }}"
                                                           @checked(in_array($permission->id, $selectedPermissionIds, true))
                                                           aria-label="{{ __(ucfirst($actionLabel)) }} {{ __($module) }} for {{ __(ucfirst($role->name)) }}"
                                                           class="size-4 rounded border-neutral-400 accent-neutral-950">
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ $roles->count() + 1 }}" class="px-5 py-8 text-center text-sm text-ink-muted">No permissions have been configured yet.</td></tr>
                        @endforelse
                    </x-ui.table>
                </x-ui.card>
            </form>
        </x-ui.tab-panel>
    </x-ui.tabs>
</x-layouts.app>
