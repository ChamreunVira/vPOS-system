@props(['variant' => 'soft'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
    match ($variant) {
        'solid' => 'bg-emerald-950 text-white',
        'outline' => 'border border-emerald-400 text-emerald-700',
        'dashed' => 'border border-dashed border-neutral-500 text-neutral-700',
        default => 'bg-emerald-100 text-emerald-800',
    },
]) }}>{{ $slot }}</span>
