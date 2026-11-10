@if (session('success'))
    <div x-data="{ open: true }" x-init="setTimeout(() => open = false, 4000)" x-show="open" x-transition
         class="mb-5 flex items-center justify-between gap-3 rounded-lg bg-neutral-950 px-4 py-3 text-sm text-white" role="status">
        <span class="flex items-center gap-2"><x-ui.icon name="check" /> {{ session('success') }}</span>
        <button type="button" @click="open = false" aria-label="Dismiss"><x-ui.icon name="x" /></button>
    </div>
@endif
