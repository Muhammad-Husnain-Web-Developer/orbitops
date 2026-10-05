<script setup>
import { File, FileArchive, FileImage, FileSpreadsheet, FileText, FileVideo, PenTool } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    kind: { type: String, default: 'file' },
    size: { type: String, default: 'md' },
});

const map = {
    image: [FileImage, 'bg-chart-2/12 text-chart-2'],
    pdf: [FileText, 'bg-danger/10 text-danger'],
    document: [FileText, 'bg-info/10 text-info'],
    spreadsheet: [FileSpreadsheet, 'bg-success/10 text-success'],
    archive: [FileArchive, 'bg-warning/10 text-warning'],
    video: [FileVideo, 'bg-chart-5/12 text-chart-5'],
    design: [PenTool, 'bg-accent/10 text-accent-text'],
    file: [File, 'bg-subtle text-ink-3'],
};

const entry = computed(() => map[props.kind] ?? map.file);
</script>

<template>
    <span class="flex shrink-0 items-center justify-center rounded-lg" :class="[entry[1], size === 'lg' ? 'size-11' : 'size-9']">
        <component :is="entry[0]" :class="size === 'lg' ? 'size-5' : 'size-4'" aria-hidden="true" />
    </span>
</template>
