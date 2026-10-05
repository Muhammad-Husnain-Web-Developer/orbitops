<script setup>
import { Head, router } from '@inertiajs/vue3';
import { Download, FolderKanban, FolderOpen, SearchX } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import FileIcon from '@/Components/Files/FileIcon.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import Select from '@/Components/UI/Select.vue';
import { swatch } from '@/lib/colors';
import { debounce } from '@/lib/debounce';
import { formatBytes, formatRelative } from '@/lib/format';

const props = defineProps({
    files: { type: Array, required: true },
    filters: { type: Object, required: true },
    projects: { type: Array, required: true },
});

const filters = reactive({ ...props.filters });
const apply = debounce(() => router.get(route('portal.files'), Object.fromEntries(Object.entries(filters).filter(([, value]) => value)), { preserveState: true, preserveScroll: true, replace: true, only: ['files', 'filters'] }), 250);
watch(filters, apply);

const projectOptions = computed(() => props.projects.map((project) => ({ value: String(project.id), label: project.name })));
const hasFilters = computed(() => Boolean(filters.search || filters.project));
</script>

<template>
    <Head title="Files" />

    <header class="mb-6">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">Files</h1>
        <p class="mt-1 text-body text-ink-3">Everything the team has shared with you: designs, documents and deliverables.</p>
    </header>

    <Card :padded="false">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
            <SearchInput v-model="filters.search" placeholder="Search files…" label="Search files" class="sm:w-72" />
            <Select v-model="filters.project" :options="projectOptions" placeholder="All projects" :icon="FolderKanban" aria-label="Filter by project" class="sm:w-56" />
        </div>
        <ul v-if="files.length" class="divide-y divide-line">
            <li v-for="file in files" :key="file.id" class="flex items-center gap-3 px-4 py-3 sm:px-5">
                <FileIcon :kind="file.kind" />
                <div class="min-w-0 flex-1">
                    <a :href="file.download_url" class="block truncate text-body font-medium text-ink hover:underline">{{ file.name }}</a>
                    <p class="flex flex-wrap items-center gap-x-2 text-caption text-ink-3">
                        <span>{{ formatBytes(file.size) }}</span><span>·</span><span>{{ formatRelative(file.created_at) }}</span>
                        <template v-if="file.project"><span>·</span><span class="inline-flex items-center gap-1"><span class="size-1.5 rounded-full" :class="swatch(file.project.color)" />{{ file.project.name }}</span></template>
                    </p>
                </div>
                <Button variant="ghost" size="sm" :icon="Download" :href="file.download_url" external :aria-label="`Download ${file.name}`" class="max-sm:px-2"><span class="max-sm:sr-only">Download</span></Button>
            </li>
        </ul>
        <EmptyState v-else-if="hasFilters" :icon="SearchX" title="No files match" description="Try a different search or project." compact />
        <EmptyState v-else :icon="FolderOpen" title="No files shared yet" description="Files the team shares with you will appear here." compact />
    </Card>
</template>
