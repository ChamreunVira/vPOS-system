@props(['amount', 'currency' => 'usd'])

@php
    $abs = abs($amount);
    $text = $currency === 'khr'
        ? '៛'.number_format(round($abs * config('vpos.exchange_rate') / 100) * 100)
        : '$'.number_format($abs, 2);
@endphp

<span {{ $attributes->class('font-money') }}>{{ $amount < 0 ? '−' : '' }}{{ $text }}</span>
