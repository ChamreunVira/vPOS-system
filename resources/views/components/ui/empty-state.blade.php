@props(['icon' => 'package', 'title', 'description' => null])

<div class="flex flex-col items-center px-6 py-12 text-center">
    <span class="flex size-12 items-center justify-center rounded-full bg-neutral-100"><x-ui.icon :name="$icon" class="size-5 text-neutral-500" /></span>
    <h3 class="mt-4 text-sm font-semibold">{{ __($title) }}</h3>
    @if ($description)<p class="mt-1 max-w-sm text-sm text-ink-muted">{{ __($description) }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
