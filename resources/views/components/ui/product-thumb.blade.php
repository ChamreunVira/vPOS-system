@props(['product', 'size' => 'md'])

{{-- Shows the uploaded image when there is one, otherwise the product emoji on a neutral tile. --}}
@php
    $image = data_get($product, 'image');
    $imageUrl = $image
        ? (str_starts_with($image, 'http') ? $image : \Illuminate\Support\Facades\Storage::disk('public')->url($image))
        : null;
    $productName = data_get($product, 'name', 'Product');
    $emoji = data_get($product, 'emoji', '');
@endphp
<div {{ $attributes->class([
    'flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100',
    match ($size) { 'sm' => 'size-9 text-lg', 'lg' => 'aspect-square w-full text-6xl', default => 'size-11 text-2xl' },
]) }}>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $productName }}" class="size-full object-cover">
    @elseif ($emoji)
        <span aria-hidden="true">{{ $emoji }}</span>
    @else
        <x-ui.icon name="package" class="size-8 text-neutral-400" aria-hidden="true" />
    @endif
</div>
