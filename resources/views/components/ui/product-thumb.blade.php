@props(['product', 'size' => 'md'])

{{-- Shows the uploaded image when there is one, otherwise the product emoji on a neutral tile. --}}
<div {{ $attributes->class([
    'flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100',
    match ($size) { 'sm' => 'size-9 text-lg', 'lg' => 'aspect-square w-full text-6xl', default => 'size-11 text-2xl' },
]) }}>
    @if ($product['image'])
        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="size-full object-cover">
    @else
        <span aria-hidden="true">{{ $product['emoji'] }}</span>
    @endif
</div>
