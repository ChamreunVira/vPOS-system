<div x-data="{ languageOpen: false }" @keydown.escape.window="languageOpen = false" @click.outside="languageOpen = false" class="relative">
    <button type="button" @click="languageOpen = ! languageOpen" :aria-expanded="languageOpen.toString()"
            aria-haspopup="menu" aria-label="{{ __('Language') }}"
            class="inline-flex h-8 items-center gap-2 rounded-lg text-sm font-medium text-neutral-700 hover:bg-neutral-50">
        <x-layout.language-flag :locale="app()->getLocale()" />
    </button>

    <div x-show="languageOpen" x-cloak x-transition.origin.top.right
         class="absolute right-0 top-full z-50 mt-2 min-w-40 rounded-lg border border-line bg-white p-1 shadow-lg"
         role="menu" aria-label="{{ __('Select language') }}">
        @foreach (['km' => 'ខ្មែរ', 'en' => 'English'] as $locale => $label)
            <form method="POST" action="{{ route('locale.update') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $locale }}">
                <button type="submit" role="menuitem" @if (app()->getLocale() === $locale) aria-current="true" @endif
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-neutral-100">
                    <x-layout.language-flag :locale="$locale" />
                    <span class="flex-1">{{ $label }}</span>
                    @if (app()->getLocale() === $locale)
                        <x-ui.icon name="check" class="size-4 text-neutral-800" />
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</div>
