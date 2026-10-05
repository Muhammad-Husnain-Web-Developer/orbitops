<script setup>
import { Folder, FolderOpen, HardDrive, Inbox, SearchX, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import Dropzone from '@/Components/Files/Dropzone.vue';
import FileList from '@/Components/Files/FileList.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import { useFilters } from '@/composables/useFilters';
import { usePermissions } from '@/composables/usePermissions';
import { swatch } from '@/lib/colors';
import { formatBytes } from '@/lib/format';

const props = defineProps({
    files: { type: Array, required: true },
    filters: { type: Object, required: true },
    projects: { type: Array, required: true },
    unfiled: { type: Number, default: 0 },
    usage: { type: Object, required: true },
});

const { can } = usePermissions();
const { filters, loading, reset } = useFilters(props.filters, { route: 'files.index', only: ['files', 'filters'], wait: 250 });
const uploading = ref(false);

const kinds = [
    { value: '', label: 'All' },
    { value: 'image', label: 'Images' },
    { value: 'pdf', label: 'PDFs' },
    { value: 'document', label: 'Docs' },
    { value: 'design', label: 'Design' },
    { value: 'archive', label: 'Archives' },
];

const folders = computed(() => [
    ...props.projects.filter((project) => project.attachments_count > 0).map((project) => ({ key: String(project.id), name: project.name, color: project.color, count: project.attachments_count })),
    ...(props.unfiled ? [{ key: 'none', name: 'General', color: null, count: props.unfiled }] : []),
]);

const usedPercent = computed(() => Math.min(100, (props.usage.bytes / (props.usage.limit_gb * 1024 ** 3)) * 100));
const activeFolder = computed(() => folders.value.find((folder) => folder.key === filters.project) ?? null);
const hasFilters = computed(() => Boolean(filters.search || filters.kind || filters.project));
const uploadData = computed(() => (filters.project && filters.project !== 'none' ? { project_id: Number(filters.project) } : {}));

function openFolder(key) {
    filters.project = filters.project === key ? '' : key;
}
</script>

<template>
    <PageHeader title="Files" description="Briefs, designs and deliverables across every project, in one place.">
        <template #actions>
            <Button v-if="can('files.manage')" :icon="Upload" @click="uploading = !uploading">{{ uploading ? 'Done' : 'Upload files' }}</Button>
        </template>
    </PageHeader>

    <div class="grid items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
        <!-- Folders & storage -->
        <aside class="hidden space-y-4 lg:block" aria-label="Folders">
            <Card :padded="false">
                <nav class="p-2" aria-label="Project folders">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-body transition-colors"
                        :class="!filters.project ? 'bg-hover text-ink' : 'text-ink-2 hover:bg-hover hover:text-ink'"
                        :aria-current="!filters.project ? 'true' : undefined"
                        @click="filters.project = ''"
                    >
                        <Inbox class="size-4 text-ink-3" />
                        <span class="flex-1">All files</span>
                        <span class="text-caption text-ink-3 tabular">{{ usage.count }}</span>
                    </button>
                    <p class="mt-3 mb-1 px-2.5 text-caption font-medium text-ink-3">Projects</p>
                    <ul class="max-h-80 overflow-y-auto lg:max-h-none">
                        <li v-for="folder in folders" :key="folder.key">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-body transition-colors"
                                :class="filters.project === folder.key ? 'bg-hover text-ink' : 'text-ink-2 hover:bg-hover hover:text-ink'"
                                :aria-current="filters.project === folder.key ? 'true' : undefined"
                                @click="openFolder(folder.key)"
                            >
                                <span class="relative">
                                    <component :is="filters.project === folder.key ? FolderOpen : Folder" class="size-4 text-ink-3" />
                                    <span v-if="folder.color" class="absolute -right-0.5 -bottom-0.5 size-1.5 rounded-full ring-2 ring-surface" :class="swatch(folder.color)" />
                                </span>
                                <span class="min-w-0 flex-1 truncate">{{ folder.name }}</span>
                                <span class="text-caption text-ink-3 tabular">{{ folder.count }}</span>
                            </button>
                        </li>
                    </ul>
                </nav>
            </Card>

            <Card>
                <div class="flex items-center gap-2 text-small font-medium text-ink"><HardDrive class="size-4 text-ink-3" />Storage</div>
                <ProgressBar class="mt-3" :value="usedPercent" size="sm" :tone="usedPercent > 85 ? 'warning' : 'accent'" label="Storage used" />
                <p class="mt-2 text-caption text-ink-3 tabular">{{ formatBytes(usage.bytes) }} of {{ usage.limit_gb }} GB used · {{ usage.count }} files</p>
            </Card>
        </aside>

        <div class="min-w-0 space-y-4">
            <!-- Mobile folders -->
            <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:hidden" aria-label="Project folders">
                <button
                    v-for="folder in [{ key: '', name: 'All files', count: usage.count }, ...folders]"
                    :key="folder.key"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-small transition-colors"
                    :class="filters.project === folder.key ? 'border-accent/40 bg-accent/10 text-accent-text' : 'border-line bg-surface text-ink-2'"
                    :aria-current="filters.project === folder.key ? 'true' : undefined"
                    @click="filters.project = folder.key"
                >
                    <span v-if="folder.color" class="size-1.5 rounded-full" :class="swatch(folder.color)" />
                    {{ folder.name }}
                    <span class="text-caption text-ink-3 tabular">{{ folder.count }}</span>
                </button>
            </nav>

            <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" leave-active-class="duration-150" leave-to-class="opacity-0">
                <Card v-if="uploading">
                    <p class="mb-3 text-small text-ink-3">
                        Uploading to <span class="font-medium text-ink">{{ activeFolder && activeFolder.key !== 'none' ? activeFolder.name : 'General (no project)' }}</span>.
                        <template v-if="!activeFolder || activeFolder.key === 'none'"> Pick a project folder first to file uploads there.</template>
                    </p>
                    <Dropzone :data="uploadData" :only="['files', 'projects', 'unfiled', 'usage']" />
                </Card>
            </Transition>

            <Card :padded="false">
                <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
                    <SearchInput v-model="filters.search" placeholder="Search files…" label="Search files" class="sm:w-72" />
                    <div class="no-scrollbar -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                        <SegmentedControl v-model="filters.kind" :options="kinds" label="Filter by type" size="sm" />
                    </div>
                </div>
                <div class="px-4 transition-opacity sm:px-5" :class="loading ? 'opacity-60' : ''" :aria-busy="loading">
                    <FileList v-if="files.length" :files="files" :show-project="!filters.project" :only="['files', 'projects', 'unfiled', 'usage']" />
                    <EmptyState v-else-if="hasFilters" :icon="SearchX" title="No files match" description="Try another folder, type or search." compact>
                        <Button variant="secondary" @click="reset()">Clear filters</Button>
                    </EmptyState>
                    <EmptyState v-else :icon="FolderOpen" title="No files yet" description="Upload briefs, designs and deliverables so everyone works from the latest version." compact>
                        <Button v-if="can('files.manage')" :icon="Upload" @click="uploading = true">Upload files</Button>
                    </EmptyState>
                </div>
            </Card>
        </div>
    </div>
</template>
