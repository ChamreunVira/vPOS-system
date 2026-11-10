@props(['tabs', 'default' => null])

{{-- Usage: <x-ui.tabs :tabs="['a' => 'First', 'b' => 'Second']"> <x-ui.tab-panel name="a">…</x-ui.tab-panel> </x-ui.tabs> --}}
<div x-data="{ tab: '{{ $default ?? array_key_first($tabs) }}' }">
    <div class="mb-5 flex gap-1 overflow-x-auto border-b border-line" role="tablist">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-neutral-950 text-neutral-950' : 'border-transparent text-neutral-500 hover:text-neutral-900'"
                    class="-mb-px whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition">{{ $label }}</button>
        @endforeach
    </div>
    {{ $slot }}
</div>
