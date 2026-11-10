{{-- Usage: <x-ui.table><x-slot:head><tr><th>Name</th></tr></x-slot:head> <tr><td>…</td></tr> </x-ui.table> --}}
<div {{ $attributes->class('overflow-x-auto') }}>
    <table class="w-full text-left text-sm [&_td]:px-5 [&_td]:py-3.5 [&_th]:px-5 [&_th]:py-3 [&_th]:font-medium">
        <thead class="bg-neutral-50 text-xs text-neutral-500">{{ $head }}</thead>
        <tbody class="divide-y divide-neutral-100">{{ $slot }}</tbody>
    </table>
</div>
