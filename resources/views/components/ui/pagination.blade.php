@props(['from' => 1, 'to' => 10, 'total' => 10])

{{-- Static demo. With real data use {{ $items->links() }} and publish Laravel's pagination views if you want this style. --}}
<div {{ $attributes->class('flex items-center justify-between border-t border-line px-5 py-3 text-sm text-ink-muted') }}>
    <span>{{ __('Showing') }} {{ $from }}–{{ $to }} {{ __('of') }} {{ $total }}</span>
    <div class="flex items-center gap-1">
        <x-ui.button variant="secondary" size="sm" icon="chevron-left" disabled>{{ __('Previous') }}</x-ui.button>
        <span class="flex size-8 items-center justify-center rounded-lg bg-neutral-950 text-xs font-medium text-white">1</span>
        <x-ui.button variant="secondary" size="sm" disabled>{{ __('Next') }} <x-ui.icon name="chevron-right" /></x-ui.button>
    </div>
</div>
