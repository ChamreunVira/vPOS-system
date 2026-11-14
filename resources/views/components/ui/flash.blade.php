@php
    $alerts = [
        'error' => [
            'message' => session('error'),
            'classes' => 'bg-red-50 text-red-900 ring-1 ring-red-200',
            'icon' => 'x',
            'role' => 'alert',
        ],
        'success' => [
            'message' => session('success'),
            'classes' => 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200',
            'icon' => 'check',
            'role' => 'status',
        ],
        'warning' => [
            'message' => session('warning'),
            'classes' => 'bg-amber-50 text-amber-950 ring-1 ring-amber-200',
            'icon' => 'info',
            'role' => 'alert',
        ],
        'info' => [
            'message' => session('info'),
            'classes' => 'bg-sky-50 text-sky-900 ring-1 ring-sky-200',
            'icon' => 'info',
            'role' => 'status',
        ],
    ];
    $alert = collect($alerts)->first(fn (array $alert): bool => filled($alert['message']));
@endphp

@if ($alert)
    <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 4000)" x-show="open" x-transition
         class="mb-5 flex items-center justify-between gap-3 rounded-lg px-4 py-3 text-sm {{ $alert['classes'] }}" role="{{ $alert['role'] }}">
        <span class="flex items-center gap-2"><x-ui.icon :name="$alert['icon']" /> {{ __($alert['message']) }}</span>
        <button type="button" @click="open = false" aria-label="{{ __('Dismiss') }}"><x-ui.icon name="x" /></button>
    </div>
@endif
