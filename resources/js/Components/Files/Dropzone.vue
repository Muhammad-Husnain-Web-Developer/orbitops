<script setup>
import { useForm } from '@inertiajs/vue3';
import { UploadCloud } from '@lucide/vue';
import { ref } from 'vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    compact: { type: Boolean, default: false },
    only: { type: Array, default: () => [] },
});

const input = ref(null);
const dragging = ref(false);
const form = useForm({ files: [], ...props.data });

function upload(fileList) {
    const files = [...fileList];
    if (!files.length) return;

    form.files = files;
    Object.assign(form, props.data);
    form.post(route('files.store'), {
        forceFormData: true,
        preserveScroll: true,
        only: props.only.length ? props.only : undefined,
        onFinish: () => {
            form.files = [];
            if (input.value) input.value.value = '';
        },
    });
}

function onDrop(event) {
    dragging.value = false;
    upload(event.dataTransfer.files);
}

const errors = () => Object.entries(form.errors).filter(([key]) => key.startsWith('files')).map(([, message]) => message);
</script>

<template>
    <div>
        <label
            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed text-center transition-colors duration-150"
            :class="[dragging ? 'border-accent bg-accent/5' : 'border-line-strong hover:border-ink-3 hover:bg-hover/50', compact ? 'px-4 py-4' : 'px-6 py-8']"
            @dragenter.prevent="dragging = true"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <input ref="input" type="file" multiple class="sr-only" :disabled="form.processing" @change="upload($event.target.files)" />
            <span class="flex size-9 items-center justify-center rounded-xl bg-accent/10 text-accent-text"><UploadCloud class="size-4" aria-hidden="true" /></span>
            <span class="mt-2.5 text-body font-medium text-ink">{{ form.processing ? 'Uploading…' : 'Drop files or click to upload' }}</span>
            <span v-if="!compact" class="mt-0.5 text-small text-ink-3">Images, PDFs, documents, design files and archives up to 20 MB</span>
        </label>
        <ProgressBar v-if="form.progress" class="mt-3" :value="form.progress.percentage" label="Upload progress" />
        <p v-for="message in errors()" :key="message" class="mt-2 text-small text-danger" role="alert">{{ message }}</p>
    </div>
</template>
