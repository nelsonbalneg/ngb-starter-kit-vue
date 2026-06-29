<script setup lang="ts">
import { ImageIcon, UploadCloud, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

/**
 * OrgLogoDropzone
 *
 * - `modelValue` (v-model)       : current logo_path string (existing URL/path for display)
 * - `file` (v-model:file)        : the selected File object sent to the backend
 *
 * The component NEVER sends base64 to the backend.
 * Files are uploaded via multipart/form-data through Inertia's useForm.
 */

type Props = {
    modelValue: string;
    file?: File | null;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'update:file': [value: File | null];
}>();

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'];
const ACCEPTED_ATTR = ACCEPTED_TYPES.join(',');
const MAX_SIZE_MB = 2;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

const isDragging = ref(false);
const localPreview = ref<string | null>(null);
const validationError = ref<string | null>(null);

// Reset preview when parent clears the form
watch(
    () => props.modelValue,
    (val) => {
        if (!val && !props.file) {
            localPreview.value = null;
        }
    },
);

watch(
    () => props.file,
    (f) => {
        if (!f) localPreview.value = null;
    },
);

const validateFile = (f: File): string | null => {
    if (!ACCEPTED_TYPES.includes(f.type)) {
        return 'Invalid file type. Accepted: JPG, PNG, SVG, WEBP.';
    }
    if (f.size > MAX_SIZE_BYTES) {
        return `File exceeds ${MAX_SIZE_MB} MB limit.`;
    }
    return null;
};

const handleFile = (f: File): void => {
    validationError.value = null;
    const error = validateFile(f);
    if (error) {
        validationError.value = error;
        return;
    }
    // Show local preview
    const reader = new FileReader();
    reader.onload = (e) => {
        const result = e.target?.result;
        if (typeof result === 'string') {
            localPreview.value = result;
        }
    };
    reader.readAsDataURL(f);

    // Emit the actual File object for form submission
    emit('update:file', f);
};

const onDrop = (e: DragEvent): void => {
    isDragging.value = false;
    const f = e.dataTransfer?.files?.[0];
    if (f) handleFile(f);
};

const onFileInput = (e: Event): void => {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    if (f) handleFile(f);
    input.value = '';
};

const clear = (): void => {
    validationError.value = null;
    localPreview.value = null;
    emit('update:modelValue', '');
    emit('update:file', null);
};

// Display priority: local preview > existing path
const displaySrc = computed(() => localPreview.value ?? props.modelValue ?? null);
const hasDisplay = computed(() => !!displaySrc.value);
const showingNewFile = computed(() => !!localPreview.value);
const showingExistingPath = computed(() => !localPreview.value && !!props.modelValue);
</script>

<template>
    <div class="space-y-1.5">
        <!-- Dropzone -->
        <div
            :class="[
                'relative flex flex-col items-center justify-center rounded-md border-2 border-dashed px-4 py-5 text-center transition-colors',
                isDragging
                    ? 'border-primary bg-primary/5'
                    : 'border-border bg-muted/30 hover:border-primary/50 hover:bg-muted/50',
                validationError ? '!border-destructive' : '',
            ]"
            @dragenter.prevent="isDragging = true"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <!-- New file preview (local FileReader result) -->
            <template v-if="showingNewFile">
                <div class="relative mb-2">
                    <img
                        :src="localPreview!"
                        alt="Logo preview"
                        class="mx-auto h-16 w-16 rounded-md object-contain ring-1 ring-border"
                    />
                    <button
                        type="button"
                        aria-label="Remove logo"
                        class="absolute -right-2 -top-2 flex size-5 items-center justify-center rounded-full bg-destructive text-white shadow-sm"
                        @click.stop="clear"
                    >
                        <X class="size-3" />
                    </button>
                </div>
                <p class="text-[11px] font-medium text-green-600 dark:text-green-400">
                    New logo selected — will be saved on submit
                </p>
                <p class="text-[11px] text-muted-foreground">Drag or click to replace</p>
            </template>

            <!-- Existing stored path -->
            <template v-else-if="showingExistingPath">
                <div class="relative mb-2">
                    <div class="flex items-center gap-2 rounded-md bg-background px-2 py-1.5 ring-1 ring-border">
                        <ImageIcon class="size-4 shrink-0 text-muted-foreground" />
                        <span class="max-w-[220px] truncate font-mono text-[11px] text-muted-foreground">
                            {{ modelValue }}
                        </span>
                    </div>
                    <button
                        type="button"
                        aria-label="Remove logo"
                        class="absolute -right-2 -top-2 flex size-5 items-center justify-center rounded-full bg-destructive text-white shadow-sm"
                        @click.stop="clear"
                    >
                        <X class="size-3" />
                    </button>
                </div>
                <p class="text-[11px] text-muted-foreground">Drag or click to replace</p>
            </template>

            <!-- Empty state -->
            <template v-else>
                <UploadCloud class="mb-2 size-6 text-muted-foreground" />
                <p class="text-xs font-medium text-foreground">Drag &amp; drop logo here</p>
                <p class="text-[11px] text-muted-foreground">or click to browse</p>
            </template>

            <!-- Hidden file input -->
            <input
                type="file"
                :accept="ACCEPTED_ATTR"
                class="absolute inset-0 cursor-pointer opacity-0"
                tabindex="-1"
                aria-label="Upload logo file"
                @change="onFileInput"
            />
        </div>

        <!-- Helper text -->
        <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-[11px] text-muted-foreground">
            <span>Accepted: JPG, PNG, SVG, WEBP</span>
            <span>Max: {{ MAX_SIZE_MB }} MB</span>
            <span>Recommended: 512 × 512 px</span>
        </div>

        <!-- Validation error -->
        <p v-if="validationError" class="text-[12px] font-medium text-destructive">
            {{ validationError }}
        </p>
    </div>
</template>
