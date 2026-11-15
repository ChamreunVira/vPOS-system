@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   aria-label="{{ trim(strip_tags((string) $slot)) }}" title="{{ trim(strip_tags((string) $slot)) }}"
   :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
   {{ $attributes->class([
       'flex min-w-0 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
       'bg-neutral-950 text-white' => $active,
       'text-neutral-600 hover:bg-neutral-200/60 hover:text-neutral-950' => ! $active,
   ]) }}>
    <x-ui.icon :name="$icon" class="size-[18px]" />
    <span :class="sidebarCollapsed ? 'lg:hidden' : ''" class="min-w-0 truncate">{{ $slot }}</span>
</a>
