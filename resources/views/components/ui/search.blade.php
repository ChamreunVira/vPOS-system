@props(['placeholder' => 'Search…', 'name' => 'q'])

<div {{ $attributes->class('relative') }}>
    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
    <input type="search" name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}"
           class="h-10 w-full rounded-lg border border-neutral-300 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900">
</div>
