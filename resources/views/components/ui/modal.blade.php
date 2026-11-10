@props(['name', 'title', 'description' => null, 'size' => 'md'])

{{-- Open with: <x-ui.button x-on:click="$dispatch('open-modal', 'name')">. Close with $dispatch('close-modal', 'name'). --}}
<div x-data="{ show: false }"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
     x-on:keydown.escape.window="show = false"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-neutral-950/40" @click="show = false"></div>
    <div x-show="show" x-transition.opacity.duration.150ms
         class="relative flex max-h-full w-full flex-col rounded-xl bg-white shadow-xl {{ ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl'][$size] ?? 'max-w-lg' }}">
        <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
            <div>
                <h2 class="text-base font-semibold">{{ $title }}</h2>
                @if ($description)<p class="mt-0.5 text-sm text-ink-muted">{{ $description }}</p>@endif
            </div>
            <button type="button" @click="show = false" class="rounded-lg p-1 text-neutral-500 hover:bg-neutral-100" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
        </header>
        <div class="overflow-y-auto px-5 py-5">{{ $slot }}</div>
        @isset($footer)
            <footer class="flex justify-end gap-2 border-t border-line px-5 py-4">{{ $footer }}</footer>
        @endisset
    </div>
</div>
