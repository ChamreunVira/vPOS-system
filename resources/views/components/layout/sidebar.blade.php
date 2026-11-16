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
            ['Expense categories', 'expense-categories.index', 'tags', 'expense-categories.*'],
            ['Payments', 'payments.index', 'wallet', 'payments.*'],
            ['Reports', 'reports.index', 'bar-chart', 'reports.*'],
        ],
        'Administration' => [
            ['Employees', 'employees.index', 'shield-check', 'employees.*'],
            ['Settings', 'settings.index', 'settings', 'settings.*'],
        ],
    ];
@endphp

<aside :class="[sidebarOpen ? '' : 'max-lg:-translate-x-full', sidebarCollapsed ? 'lg:w-20' : 'lg:w-64']"
       id="main-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-neutral-50 transition-[width,transform] duration-200 lg:sticky lg:bottom-auto lg:left-auto lg:top-0 lg:h-screen lg:shrink-0">
    <div :class="sidebarCollapsed ? 'lg:justify-center lg:gap-0 lg:px-3' : ''" class="flex h-16 shrink-0 items-center gap-3 px-5">
        <img src="{{ asset('images/v.png') }}" alt="v-logo" class="w-6 h-8 object-cover">
        {{-- <span class="flex size-9 items-center justify-center rounded-lg bg-neutral-950 text-base font-semibold text-white">v</span> --}}
        <div :class="sidebarCollapsed ? 'lg:hidden' : ''" class="min-w-0 leading-tight">
            <p class="truncate text-base font-semibold">{{ config('vpos.name') }}</p>
            <p class="text-xs text-ink-muted">{{ __('Mini mart POS') }}</p>
        </div>
        <button type="button" @click="sidebarOpen = false" aria-label="{{ __('Close menu') }}" title="{{ __('Close menu') }}"
                class="ml-auto shrink-0 rounded-lg p-2 text-neutral-600 hover:bg-neutral-200/70 lg:hidden">
            <x-ui.icon name="x" class="size-4" />
        </button>
        <button type="button" @click="toggleSidebarCollapsed()" :aria-pressed="sidebarCollapsed.toString()"
                :title="sidebarCollapsed ? @js(__('Expand sidebar')) : @js(__('Collapse sidebar'))"
                aria-label="{{ __('Toggle sidebar width') }}"
                class="ml-auto hidden shrink-0 rounded-lg p-2 text-neutral-600 hover:bg-neutral-200/70 lg:inline-flex">
            <x-ui.icon name="chevron-left" class="size-4 transition-transform" x-bind:class="sidebarCollapsed ? 'rotate-180' : ''" />
        </button>
    </div>

    <nav @click="sidebarOpen = false" class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="{{ __('Main') }}">
        @foreach ($groups as $group => $items)
            <div>
                <p :class="sidebarCollapsed ? 'lg:hidden' : ''" class="mb-1.5 px-3 text-xs font-medium text-neutral-400">{{ __($group) }}</p>
                <div class="space-y-0.5">
                    @foreach ($items as [$label, $route, $icon, $pattern])
                        <x-layout.nav-link :href="route($route)" :icon="$icon" :active="request()->routeIs($pattern)">{{ __($label) }}</x-layout.nav-link>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div :class="sidebarCollapsed ? 'lg:mx-2 lg:justify-center lg:gap-1 lg:border-0 lg:bg-transparent lg:p-0' : ''"
         title="{{ $user?->name ?? __('User') }} · {{ $user?->role?->name ?? __('Staff') }}"
         class="m-3 flex items-center gap-3 rounded-xl border border-line bg-white p-3">
        <x-ui.avatar :name="$user?->name ?? __('User')" x-bind:class="sidebarCollapsed ? 'lg:size-7 lg:text-[10px]' : ''" />
        <div :class="sidebarCollapsed ? 'lg:hidden' : ''" class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-sm font-medium">{{ $user?->name ?? __('User') }}</p>
            <p class="text-xs text-ink-muted">{{ $user?->role?->name ?? __('Staff') }}</p>
        </div>
        <button type="button" class="rounded-lg p-1.5 text-neutral-500 hover:bg-neutral-100" aria-label="{{ __('Log out') }}" title="{{ __('Log out') }}"><x-ui.icon name="log-out" /></button>
    </div>
</aside>
