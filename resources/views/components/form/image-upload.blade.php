@props(['label' => 'Image', 'name' => 'image', 'current' => null, 'hint' => 'PNG or JPG, up to 2 MB'])

{{-- Previews the chosen file in the browser. Remember enctype="multipart/form-data" on the form. --}}
<div x-data="{ preview: @js($current) }" class="space-y-1.5">
    <span class="block text-sm font-medium text-neutral-900">{{ $label }}</span>
    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-center hover:bg-neutral-100">
        <template x-if="preview"><img :src="preview" alt="" class="size-32 rounded-lg object-cover"></template>
        <template x-if="! preview"><span class="flex size-12 items-center justify-center rounded-full bg-white"><x-ui.icon name="image" class="size-5 text-neutral-500" /></span></template>
        <span class="text-sm font-medium">Upload {{ strtolower($label) }}</span>
        <span class="text-xs text-ink-muted">{{ $hint }}</span>
        <input type="file" name="{{ $name }}" accept="image/*" class="sr-only"
               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview">
    </label>
</div>
