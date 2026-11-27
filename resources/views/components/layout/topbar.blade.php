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
        <button type="button"
                x-data="{
                    isFullscreen: Boolean(document.fullscreenElement),
                    async toggleFullscreen() {
                        try {
                            if (document.fullscreenElement) {
                                await document.exitFullscreen();
                            } else {
                                await document.documentElement.requestFullscreen();
                            }
                        } catch {
                            this.isFullscreen = Boolean(document.fullscreenElement);
                        }
                    },
                }"
                @click="toggleFullscreen()"
                @fullscreenchange.window="isFullscreen = Boolean(document.fullscreenElement)"
                :aria-pressed="isFullscreen.toString()"
                :aria-label="isFullscreen ? '{{ __('Exit full screen') }}' : '{{ __('Enter full screen') }}'"
                :title="isFullscreen ? '{{ __('Exit full screen') }}' : '{{ __('Enter full screen') }}'"
                class="rounded-lg p-2 text-neutral-600 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-400"
        >
            <x-ui.icon x-show="!isFullscreen" name="maximize" class="size-[18px]" />
            <x-ui.icon x-show="isFullscreen" name="minimize" class="size-[18px]" />
        </button>
        <button type="button" class="rounded-lg p-2 text-neutral-600 hover:bg-neutral-100" aria-label="{{ __('Notifications') }}"><x-ui.icon name="bell" class="size-[18px]" /></button>
        <x-ui.button variant="ghost" size="sm" icon="lock" aria-label="{{ __('Lock') }}" class="max-sm:px-2"><span class="hidden sm:inline">{{ __('Lock') }}</span></x-ui.button>
    </div>
</header>
