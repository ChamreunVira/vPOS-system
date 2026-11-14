@props(['label' => null, 'name', 'value' => null, 'hint' => null, 'rows' => 3])

<div class="space-y-1.5">
    @if ($label)<label for="{{ $name }}" class="block text-sm font-medium text-neutral-900">{{ __($label) }}</label>@endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              {{ $attributes->class('w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900') }}>{{ old($name, $value) }}</textarea>
    @if ($hint)<p class="text-xs text-ink-muted">{{ __($hint) }}</p>@endif
    @error($name)<p class="text-xs font-medium text-neutral-950">{{ $message }}</p>@enderror
</div>
