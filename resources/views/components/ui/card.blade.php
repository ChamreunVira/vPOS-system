@props(['title' => null, 'description' => null, 'padded' => true])

@php $hasHeader = $title || isset($actions); @endphp

<section {{ $attributes->class('rounded-xl border border-line bg-surface') }}>
    @if ($hasHeader)
        <header class="flex items-start justify-between gap-4 px-5 pt-5">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-neutral-950">{{ $title }}</h2>
                @if ($description)<p class="mt-0.5 text-sm text-ink-muted">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['p-5' => $padded && ! $hasHeader, 'px-5 pb-5 pt-4' => $padded && $hasHeader, 'mt-4' => ! $padded && $hasHeader])>
        {{ $slot }}
    </div>
</section>
