@props(['title', 'subtitle' => null, 'back' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-sm text-ink-muted hover:text-neutral-950">
                <x-ui.icon name="arrow-left" /> {{ __('Back') }}
            </a>
        @endif
        <h1 class="text-2xl font-semibold tracking-tight text-neutral-950">{{ __($title) }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-ink-muted">{{ __($subtitle) }}</p>@endif
    </div>
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
