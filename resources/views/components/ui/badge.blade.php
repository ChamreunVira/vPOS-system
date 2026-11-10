@props(['variant' => 'soft'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
    match ($variant) {
        'solid' => 'bg-neutral-950 text-white',
        'outline' => 'border border-neutral-400 text-neutral-700',
        'dashed' => 'border border-dashed border-neutral-500 text-neutral-700',
        default => 'bg-neutral-100 text-neutral-800',
    },
]) }}>{{ $slot }}</span>
