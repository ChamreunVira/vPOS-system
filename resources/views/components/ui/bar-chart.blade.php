@props(['data', 'height' => 'h-56'])

{{-- $data: [['label' => 'Mon', 'value' => 120, 'previous' => 100], …]. Pure CSS, no JS; swap for Chart.js when you need more. --}}
@php
    $max = max(1, ...collect($data)->flatMap(fn ($point) => [$point['value'], $point['previous'] ?? 0])->all());
@endphp

<div {{ $attributes->class(['flex items-stretch gap-3', $height]) }}>
    @foreach ($data as $point)
        <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
            <div class="flex w-full flex-1 items-end justify-center gap-1">
                @isset($point['previous'])
                    <div class="w-full max-w-4 rounded-t bg-neutral-200" style="height: {{ round($point['previous'] / $max * 100) }}%" title="Previous: {{ $point['previous'] }}"></div>
                @endisset
                <div class="w-full max-w-4 rounded-t bg-neutral-950" style="height: {{ round($point['value'] / $max * 100) }}%" title="{{ $point['label'] }}: {{ $point['value'] }}"></div>
            </div>
            <span class="text-xs text-ink-muted">{{ $point['label'] }}</span>
        </div>
    @endforeach
</div>
