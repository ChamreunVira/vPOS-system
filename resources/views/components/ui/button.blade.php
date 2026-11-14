@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null, 'type' => 'button'])

@php
    $plainText = trim(strip_tags((string) $slot));
    $translatedText = $plainText !== '' ? __($plainText) : null;
    $hasTranslation = $translatedText !== null
        && $translatedText !== $plainText
        && ! str_contains((string) $slot, '<');
    $classes = [
        'inline-flex items-center justify-center gap-2 rounded-lg font-medium whitespace-nowrap transition',
        'disabled:pointer-events-none disabled:opacity-40',
        match ($size) {
            'sm' => 'h-8 px-3 text-xs',
            'lg' => 'h-12 px-5 text-base',
            default => 'h-10 px-4 text-sm',
        },
        match ($variant) {
            'secondary' => 'border border-neutral-300 bg-white text-neutral-900 hover:bg-neutral-50',
            'ghost' => 'text-neutral-700 hover:bg-neutral-100',
            'danger' => 'border border-neutral-950 bg-white text-neutral-950 hover:bg-neutral-950 hover:text-white',
            default => 'bg-neutral-950 text-white hover:bg-neutral-800',
        },
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-ui.icon :name="$icon" />@endif
        @if ($hasTranslation){{ $translatedText }}@else{{ $slot }}@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-ui.icon :name="$icon" />@endif
        @if ($hasTranslation){{ $translatedText }}@else{{ $slot }}@endif
    </button>
@endif
