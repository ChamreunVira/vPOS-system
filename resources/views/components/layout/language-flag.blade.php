@props(['locale'])

@if ($locale === 'km')
    <img src="{{ asset('images/cambodia-flag-round-circle-icon.svg') }}" alt="" class="h-6 w-6 shrink-0 rounded-sm" aria-hidden="true">
@else
    <img src="{{ asset('images/uk-flag-round-circle-icon.svg') }}" alt="" class="h-6 w-6 shrink-0 rounded-sm" aria-hidden="true">
@endif