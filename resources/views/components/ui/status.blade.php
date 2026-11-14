@props(['status', 'label' => null])

{{-- One place that decides how every status looks. Add new statuses to the map. --}}
@php
    $variant = match ($status) {
        'low_stock', 'out_of_stock', 'stock_out' => 'solid',
        'pending', 'unpaid', 'inactive', 'cancelled' => 'outline',
        'refunded', 'partially_refunded', 'return' => 'dashed',
        default => 'soft',
    };
    $text = __($label ?? ucfirst(str_replace('_', ' ', $status)));
@endphp

<x-ui.badge :variant="$variant" {{ $attributes }}>{{ $text }}</x-ui.badge>
