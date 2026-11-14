@props(['label' => null, 'name', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])

{{-- $options may be ['value' => 'Label'] or a plain list of strings. --}}
@php $isList = array_is_list($options); @endphp

<div class="space-y-1.5">
    @if ($label)<label for="{{ $name }}" class="block text-sm font-medium text-neutral-900">{{ __($label) }}</label>@endif
    <select id="{{ $name }}" name="{{ $name }}"
            {{ $attributes->class('h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900') }}>
        @if ($placeholder)<option value="">{{ __($placeholder) }}</option>@endif
        @foreach ($options as $key => $text)
            @php $optionValue = $isList ? $text : $key; @endphp
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ __($text) }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="text-xs text-ink-muted">{{ __($hint) }}</p>@endif
    @error($name)<p class="text-xs font-medium text-neutral-950">{{ $message }}</p>@enderror
</div>
