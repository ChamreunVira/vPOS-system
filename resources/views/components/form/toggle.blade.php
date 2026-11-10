@props(['name', 'label', 'hint' => null, 'checked' => false])

{{-- Posts "1" when on and "0" when off. Usage: <x-form.toggle name="status" label="Active" :checked="true" /> --}}
<label class="flex cursor-pointer items-start justify-between gap-4">
    <span>
        <span class="block text-sm font-medium text-neutral-900">{{ $label }}</span>
        @if ($hint)<span class="block text-xs text-ink-muted">{{ $hint }}</span>@endif
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked)) {{ $attributes->class('peer sr-only') }}>
        <span class="h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-neutral-950 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-neutral-900"></span>
        <span class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
