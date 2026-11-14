@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'placeholder' => null, 'prefix' => null, 'suffix' => null])

{{-- Usage: <x-form.input label="Selling price" name="price" type="number" step="0.01" prefix="$" :value="$product['price'] ?? null" /> --}}
<div class="space-y-1.5">
    @if ($label)<label for="{{ $name }}" class="block text-sm font-medium text-neutral-900">{{ __($label) }}</label>@endif
    <div class="flex h-10 items-center rounded-lg border bg-white focus-within:ring-1 focus-within:ring-neutral-900 {{ $errors->has($name) ? 'border-neutral-950 ring-1 ring-neutral-950' : 'border-neutral-300 focus-within:border-neutral-900' }}">
        @if ($prefix)<span class="pl-3 text-sm text-neutral-500">{{ $prefix }}</span>@endif
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @if ($placeholder) placeholder="{{ __($placeholder) }}" @endif
               {{ $attributes->class('h-full w-full min-w-0 bg-transparent px-3 text-sm placeholder:text-neutral-400 focus:outline-none') }}>
        @if ($suffix)<span class="pr-3 text-sm text-neutral-500">{{ $suffix }}</span>@endif
    </div>
    @if ($hint)<p class="text-xs text-ink-muted">{{ __($hint) }}</p>@endif
    @error($name)<p class="text-xs font-medium text-neutral-950">{{ $message }}</p>@enderror
</div>
