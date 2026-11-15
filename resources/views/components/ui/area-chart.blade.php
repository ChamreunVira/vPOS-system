
@props([
    'data' => [],
    'height' => 'h-72',
])

@php
    $chartId = 'sales-chart-' . \Illuminate\Support\Str::uuid();
@endphp

<div
    x-data="salesChart({{ Js::from($data) }})"
    x-init="init()"
    {{ $attributes->class([$height, 'relative w-full select-none']) }}
>
    <svg
        class="absolute inset-0 h-full w-full overflow-visible"
        :viewBox="`0 0 ${Math.max(width, 1)} ${Math.max(containerHeight, 1)}`"
        preserveAspectRatio="xMidYMid meet"
        @mousemove="onMouseMove($event)"
        @mouseleave="onMouseLeave()"
        aria-hidden="true"
    >
        <defs>
            {{-- Current series gradient --}}
            <linearGradient id="{{ $chartId }}-current" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#09090b" stop-opacity="0.18" />
                <stop offset="100%" stop-color="#09090b" stop-opacity="0" />
            </linearGradient>

            {{-- Previous series gradient --}}
            <linearGradient id="{{ $chartId }}-previous" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#a3a3a3" stop-opacity="0.14" />
                <stop offset="100%" stop-color="#a3a3a3" stop-opacity="0" />
            </linearGradient>
        </defs>

        <template x-if="width > 0 && containerHeight > 0">
            <g>
                {{-- Y-axis grid lines + labels --}}
                <template x-for="(tick, ti) in yTicks" :key="ti">
                    <g>
                        <line
                            :x1="paddingLeft"
                            :x2="width - paddingRight"
                            :y1="yPos(tick)"
                            :y2="yPos(tick)"
                            stroke="#e5e7eb"
                            stroke-width="1"
                        />

                        <text
                            :x="paddingLeft - 8"
                            :y="yPos(tick) + 4"
                            text-anchor="end"
                            style="font-size: 10px; fill: #9ca3af"
                            x-text="formatMoney(tick)"
                        />
                    </g>
                </template>

                {{-- Previous area --}}
                <path
                    :d="buildSmoothAreaPath('previous')"
                    fill="url(#{{ $chartId }}-previous)"
                />

                {{-- Previous line --}}
                <path
                    :d="buildSmoothPath('previous')"
                    fill="none"
                    stroke="#d4d4d8"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                {{-- Current area --}}
                <path
                    :d="buildSmoothAreaPath('value')"
                    fill="url(#{{ $chartId }}-current)"
                />

                {{-- Current line --}}
                <path
                    :d="buildSmoothPath('value')"
                    fill="none"
                    stroke="#09090b"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                {{-- X-axis labels --}}
                <template x-for="(point, i) in series" :key="i">
                    <text
                        :x="xPos(i)"
                        :y="paddingTop + chartHeight + 22"
                        text-anchor="middle"
                        style="font-size: 11px; fill: #9ca3af"
                        x-text="point.label"
                    />
                </template>

                {{-- Hover crosshair --}}
                <template x-if="hoverIndex !== null">
                    <g>
                        <line
                            :x1="xPos(hoverIndex)"
                            :x2="xPos(hoverIndex)"
                            :y1="paddingTop"
                            :y2="paddingTop + chartHeight"
                            stroke="#09090b"
                            stroke-width="1"
                            stroke-dasharray="3 3"
                            opacity="0.4"
                        />

                        {{-- Previous dot --}}
                        <circle
                            :cx="xPos(hoverIndex)"
                            :cy="yPos(series[hoverIndex]?.previous ?? 0)"
                            r="4"
                            fill="white"
                            stroke="#d4d4d8"
                            stroke-width="2"
                        />

                        {{-- Current dot --}}
                        <circle
                            :cx="xPos(hoverIndex)"
                            :cy="yPos(series[hoverIndex]?.value ?? 0)"
                            r="4"
                            fill="#09090b"
                            stroke="white"
                            stroke-width="2"
                        />
                    </g>
                </template>
            </g>
        </template>
    </svg>

    {{-- Hover tooltip --}}
    <div
        x-show="tooltip.visible && hoverIndex !== null"
        x-cloak
        :style="`left:${tooltip.x}px;top:${tooltip.y}px`"
        class="pointer-events-none absolute z-10 min-w-[130px] rounded-lg border border-neutral-100 bg-white p-3 shadow-sm"
    >
        <template x-if="hoverIndex !== null">
            <div>
                <p
                    class="mb-2 text-xs font-medium text-neutral-500"
                    x-text="series[hoverIndex]?.label"
                ></p>

                <div class="space-y-1.5">
                    {{-- Current --}}
                    <div class="flex items-center justify-between gap-4">
                        <span class="flex items-center gap-1.5 text-xs text-neutral-500">
                            <span class="inline-block size-2 rounded-sm bg-neutral-950"></span>
                            Current
                        </span>

                        <span
                            class="text-xs font-semibold text-neutral-900"
                            x-text="formatMoney(series[hoverIndex]?.value ?? 0)"
                        ></span>
                    </div>

                    {{-- Previous --}}
                    <div class="flex items-center justify-between gap-4">
                        <span class="flex items-center gap-1.5 text-xs text-neutral-500">
                            <span class="inline-block size-2 rounded-sm bg-neutral-300"></span>
                            Previous
                        </span>

                        <span
                            class="text-xs font-semibold text-neutral-500"
                            x-text="formatMoney(series[hoverIndex]?.previous ?? 0)"
                        ></span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
