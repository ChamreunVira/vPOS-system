<header class="sticky top-0 z-30 flex h-16 min-w-0 items-center justify-between gap-2 border-b border-line bg-white/90 px-3 backdrop-blur sm:px-8">
    <div class="flex min-w-0 items-center gap-2 sm:gap-3">
        <button type="button" class="rounded-lg p-2 hover:bg-neutral-100 lg:hidden" @click="sidebarOpen = true" :aria-expanded="sidebarOpen.toString()" aria-controls="main-sidebar" aria-label="{{ __('Open menu') }}"><x-ui.icon name="menu" class="size-5" /></button>
        <x-ui.icon name="store" class="size-[18px] text-neutral-500" />
        <span class="min-w-0 truncate text-sm font-medium">{{ config('vpos.store') }}</span>
        <span class="hidden text-sm text-ink-muted sm:inline">{{ config('vpos.register') }}</span>
    </div>
    <div class="flex shrink-0 items-center gap-1.5 sm:gap-4">
        <x-ui.badge class="hidden sm:inline-flex">● {{ __('Online') }}</x-ui.badge>
        <span class="hidden text-sm text-ink-muted md:inline">{{ now()->locale(app()->getLocale())->translatedFormat('D, M j, Y') }}</span>
        <x-layout.language-switcher />
        <button type="button" class="rounded-lg p-2 text-neutral-600 hover:bg-neutral-100" aria-label="{{ __('Notifications') }}"><x-ui.icon name="bell" class="size-[18px]" /></button>
        <x-ui.button variant="ghost" size="sm" icon="lock" aria-label="{{ __('Lock') }}" class="max-sm:px-2"><span class="hidden sm:inline">{{ __('Lock') }}</span></x-ui.button>
    </div>
</header>
