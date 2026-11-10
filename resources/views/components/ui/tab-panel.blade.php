@props(['name'])

<div x-show="tab === '{{ $name }}'" x-cloak role="tabpanel">{{ $slot }}</div>
