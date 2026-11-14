<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-line bg-white/90 px-4 backdrop-blur sm:px-8">
    <div class="flex items-center gap-3">
        <button type="button" class="rounded-lg p-2 hover:bg-neutral-100 lg:hidden" @click="sidebarOpen = true" aria-label="{{ __('Open menu') }}"><x-ui.icon name="menu" class="size-5" /></button>
        <x-ui.icon name="store" class="size-[18px] text-neutral-500" />
        <span class="text-sm font-medium">{{ config('vpos.store') }}</span>
        <span class="hidden text-sm text-ink-muted sm:inline">{{ config('vpos.register') }}</span>
    </div>
    <div class="flex items-center gap-2 sm:gap-4">
        <x-ui.badge class="hidden sm:inline-flex">● {{ __('Online') }}</x-ui.badge>
        <span class="hidden text-sm text-ink-muted md:inline">{{ now()->locale(app()->getLocale())->translatedFormat('D, M j, Y') }}</span>
        <x-layout.language-switcher />
        <button type="button" class="rounded-lg p-2 text-neutral-600 hover:bg-neutral-100" aria-label="{{ __('Notifications') }}"><x-ui.icon name="bell" class="size-[18px]" /></button>
        <x-ui.button variant="ghost" size="sm" icon="lock">{{ __('Lock') }}</x-ui.button>
    </div>
</header>
