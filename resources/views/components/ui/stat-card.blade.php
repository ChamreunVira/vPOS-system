@props(['label', 'delta' => null, 'hint' => null, 'icon' => null])

{{-- The slot is the big value, e.g. <x-ui.money :amount="1284.5" /> or a plain number. --}}
<div {{ $attributes->class('rounded-xl border border-line bg-surface p-5') }}>
    <div class="flex items-center justify-between text-sm text-ink-muted">
        <span>{{ $label }}</span>
        @if ($icon)<x-ui.icon :name="$icon" class="size-4 text-neutral-400" />@endif
    </div>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950">{{ $slot }}</p>
    @if (! is_null($delta))
        <p class="mt-1.5 flex items-center gap-1 text-xs text-ink-muted">
            <span class="font-medium text-neutral-900">{{ $delta >= 0 ? '↑' : '↓' }} {{ abs($delta) }}%</span> vs. yesterday
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
</div>
