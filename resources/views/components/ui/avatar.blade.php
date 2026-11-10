@props(['name', 'size' => 'md'])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center justify-center rounded-full bg-neutral-950 font-medium text-white',
    match ($size) { 'sm' => 'size-7 text-[10px]', 'lg' => 'size-14 text-lg', default => 'size-9 text-xs' },
]) }}>{{ $initials }}</span>
