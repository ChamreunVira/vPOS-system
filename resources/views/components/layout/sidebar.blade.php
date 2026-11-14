@php
    $user = auth()->user();

    // [label, route name, icon, route pattern that marks it active]
    $groups = [
        'Workspace' => [
            ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
            ['Point of sale', 'pos.index', 'cart', 'pos.*'],
            ['Orders', 'orders.index', 'receipt', 'orders.*'],
            ['Customers', 'customers.index', 'users', 'customers.*'],
        ],
        'Catalogue' => [
            ['Products', 'products.index', 'package', 'products.*'],
            ['Categories', 'categories.index', 'tags', 'categories.*'],
            ['Units', 'units.index', 'ruler', 'units.*'],
            ['Inventory', 'inventory.index', 'layers', 'inventory.*'],
            ['Suppliers', 'suppliers.index', 'truck', 'suppliers.*'],
        ],
        'Finance' => [
            ['Expenses', 'expenses.index', 'banknote', 'expenses.*'],
            ['Payments', 'payments.index', 'wallet', 'payments.*'],
            ['Reports', 'reports.index', 'bar-chart', 'reports.*'],
        ],
        'Administration' => [
            ['Employees', 'employees.index', 'shield-check', 'employees.*'],
            ['Settings', 'settings.index', 'settings', 'settings.*'],
        ],
    ];
@endphp

<aside :class="sidebarOpen ? '' : 'max-lg:-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-neutral-50 transition-transform">
    <div class="flex h-16 shrink-0 items-center gap-3 px-5">
        <span class="flex size-9 items-center justify-center rounded-lg bg-neutral-950 text-base font-semibold text-white">v</span>
        <div class="leading-tight">
            <p class="text-base font-semibold">{{ config('vpos.name') }}</p>
            <p class="text-xs text-ink-muted">{{ __('Mini mart POS') }}</p>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="{{ __('Main') }}">
        @foreach ($groups as $group => $items)
            <div>
                <p class="mb-1.5 px-3 text-xs font-medium text-neutral-400">{{ __($group) }}</p>
                <div class="space-y-0.5">
                    @foreach ($items as [$label, $route, $icon, $pattern])
                        <x-layout.nav-link :href="route($route)" :icon="$icon" :active="request()->routeIs($pattern)">{{ __($label) }}</x-layout.nav-link>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="m-3 flex items-center gap-3 rounded-xl border border-line bg-white p-3">
        <x-ui.avatar :name="$user?->name ?? __('User')" />
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-sm font-medium">{{ $user?->name ?? __('User') }}</p>
            <p class="text-xs text-ink-muted">{{ $user?->role?->name ?? __('Staff') }}</p>
        </div>
        <button type="button" class="rounded-lg p-1.5 text-neutral-500 hover:bg-neutral-100" aria-label="{{ __('Log out') }}"><x-ui.icon name="log-out" /></button>
    </div>
</aside>
