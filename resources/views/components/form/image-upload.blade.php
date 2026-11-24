
@props([
    'label' => 'Image',
    'name' => 'image',
    'current' => null,
    'hint' => 'PNG or JPG, up to 2 MB',
])

{{-- Image upload with drag-and-drop and preview --}}
<div
    x-data="{
        preview: @js($current),
        dragging: false,

        selectFile(file) {
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                return;
            }

            if (this.preview?.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }

            this.preview = URL.createObjectURL(file);

            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.input.files = transfer.files;

            this.$dispatch('image-selected', { file });
        },

        handleDrop(event) {
            this.dragging = false;
            this.selectFile(event.dataTransfer.files[0]);
        },

        removeImage() {
            if (this.preview?.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }

            this.preview = null;
            this.$refs.input.value = '';
        },

        destroy() {
            if (this.preview?.startsWith('blob:')) {
                URL.revokeObjectURL(this.preview);
            }
        }
    }"
    class="space-y-1.5"
>
    <span class="block text-sm font-medium text-neutral-900">
        {{ $label }}
    </span>

    <input
        x-ref="input"
        type="file"
        name="{{ $name }}"
        accept="image/*"
        class="sr-only"
        @change="selectFile($event.target.files[0])"
    >

    {{-- Drop zone --}}
    <label
        @dragenter.prevent="dragging = true"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.input.click()"
        :class="dragging
            ? 'border-primary-500 bg-primary-50'
            : 'border-neutral-300 bg-neutral-50 hover:bg-neutral-100'"
        class="flex min-h-48 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-6 text-center transition-colors"
    >
        {{-- Image preview --}}
        <template x-if="preview">
            <img
                :src="preview"
                alt="Image preview"
                class="size-32 rounded-lg object-cover"
            >
        </template>

        {{-- Empty state --}}
        <template x-if="!preview">
            <span class="flex size-12 items-center justify-center rounded-full bg-white">
                <x-ui.icon name="image" class="size-5 text-neutral-500" />
            </span>
        </template>

        <span class="text-sm font-medium text-neutral-900">
            <span x-show="!preview">
                Drop your image here or click to upload
            </span>

            <span x-show="preview">
                Click or drop another image to replace
            </span>
        </span>

        <span class="text-xs text-ink-muted">
            {{ $hint }}
        </span>
    </label>

    {{-- Remove selected image --}}
    <div x-show="preview" x-cloak class="flex justify-end">
        <button
            type="button"
            @click="removeImage()"
            class="text-xs font-medium text-red-600 hover:text-red-700"
        >
            Remove image
        </button>
    </div>

    {{-- Validation error --}}
    @error($name)
        <p class="text-xs font-medium text-red-700">
            {{ $message }}
        </p>
    @enderror
</div>
