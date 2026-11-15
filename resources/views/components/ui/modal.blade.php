@props(['name', 'title', 'description' => null, 'size' => 'md'])

{{-- Open with: <x-ui.button x-on:click="$dispatch('open-modal', 'name')">. Close with $dispatch('close-modal', 'name'). --}}
<div x-data="{ show: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
     x-on:keydown.escape.window="show = false"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-neutral-950/40" @click="show = false"></div>
    <div x-show="show" x-transition.opacity.duration.150ms
         class="relative flex max-h-[calc(100dvh-1.5rem)] w-full min-w-0 flex-col rounded-xl bg-white shadow-xl sm:max-h-[calc(100dvh-2rem)] {{ ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl'][$size] ?? 'max-w-lg' }}">
        <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
            <div class="min-w-0">
                <h2 class="break-words text-base font-semibold">{{ __($title) }}</h2>
                @if ($description)<p class="mt-0.5 text-sm text-ink-muted">{{ __($description) }}</p>@endif
            </div>
            <button type="button" @click="show = false" class="rounded-lg p-1 text-neutral-500 hover:bg-neutral-100" aria-label="{{ __('Close') }}"><x-ui.icon name="x" class="size-5" /></button>
        </header>
        <div class="min-h-0 overflow-y-auto px-4 py-5 sm:px-5">{{ $slot }}</div>
        @isset($footer)
            <footer class="flex flex-wrap justify-end gap-2 border-t border-line px-4 py-4 sm:px-5">{{ $footer }}</footer>
        @endisset
    </div>
</div>
