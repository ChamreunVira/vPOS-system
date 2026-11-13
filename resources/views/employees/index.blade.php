<x-layouts.app title="Employees">
    <x-ui.page-header title="Employees" subtitle="Who can sign in, and what they can do.">
        <x-ui.button :href="route('employees.create')" icon="user-plus">Add employee</x-ui.button>
    </x-ui.page-header>

    <x-ui.tabs :tabs="['employees' => 'Employees', 'roles' => 'Roles & permissions']">
        <x-ui.tab-panel name="employees">
            <x-ui.card :padded="false">
                <x-ui.table>
                    <x-slot:head><tr><th>Employee</th><th>Phone</th><th>Role</th><th>Last sign-in</th><th>Status</th><th></th></tr></x-slot:head>
                    @foreach ($employees as $employee)
                        <tr>
                            <td><span class="flex items-center gap-3"><x-ui.avatar :name="$employee['name']" size="sm" /><span><span class="block font-medium">{{ $employee['name'] }}</span><span class="text-xs text-ink-muted">{{ $employee['email'] }}</span></span></span></td>
                            <td class="font-money text-neutral-600">{{ $employee['phone'] ?? "N/A"}}</td>
                            <td><x-ui.badge :variant="$employee->role?->name === 'admin' ? 'solid' : 'soft'">{{ $employee->role?->name ?? 'Unassigned' }}</x-ui.badge></td>
                            <td class="text-neutral-600">{{ $employee['last_login'] ?? "N/A" }}</td>
                            <td><x-ui.status :status="$employee['status']" /></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <x-ui.button :href="route('employees.edit', $employee['id'])" variant="ghost" size="sm" icon="pencil" aria-label="Edit" />
                                    <form method="POST" action="{{ route('employees.destroy', $employee['id']) }}" onsubmit="return confirm('Remove this employee?')">
                                        @csrf @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" aria-label="Remove" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab-panel>

        <x-ui.tab-panel name="roles">
            <form method="POST" action="{{ route('employees.index') }}">
                <x-ui.card :padded="false" title="Permission matrix" description="Tick what each role may do in every module.">
                    <x-slot:actions><x-ui.button type="button" icon="check" size="sm">Save permissions</x-ui.button></x-slot:actions>
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <th>Module</th>
                                @foreach ($roles['names'] as $role)
                                    <th class="text-center">{{ $role }}<span class="block text-[11px] font-normal text-neutral-400">view · create · edit · delete</span></th>
                                @endforeach
                            </tr>
                        </x-slot:head>
                        @foreach ($roles['modules'] as $module)
                            <tr>
                                <td class="font-medium">{{ $module }}</td>
                                @foreach ($roles['names'] as $index => $role)
                                    <td>
                                        <div class="flex justify-center gap-3">
                                            @foreach ($roles['actions'] as $action)
                                                <input type="checkbox" name="permissions[{{ $role }}][{{ $module }}][]" value="{{ $action }}" title="{{ ucfirst($action) }}"
                                                       @checked(in_array($action, $roles['grants'][$module][$index], true)) class="size-4 rounded border-neutral-400 accent-neutral-950">
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </x-ui.table>
                </x-ui.card>
            </form>
        </x-ui.tab-panel>
    </x-ui.tabs>
</x-layouts.app>
