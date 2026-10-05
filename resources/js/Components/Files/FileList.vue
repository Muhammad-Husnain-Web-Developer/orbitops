<script setup>
import { router } from '@inertiajs/vue3';
import { Download, Eye, EyeOff, FolderOpen, MoreHorizontal, Trash2 } from '@lucide/vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { confirm } from '@/composables/useConfirm';
import { usePermissions } from '@/composables/usePermissions';
import { swatch } from '@/lib/colors';
import { formatBytes, formatRelative } from '@/lib/format';
import FileIcon from './FileIcon.vue';

const props = defineProps({
    files: { type: Array, required: true },
    showProject: { type: Boolean, default: false },
    only: { type: Array, default: () => [] },
    emptyTitle: { type: String, default: 'No files yet' },
    emptyDescription: { type: String, default: 'Upload briefs, designs and deliverables so everyone works from the latest version.' },
});

const { can } = usePermissions();
const options = () => ({ preserveScroll: true, only: props.only.length ? props.only : undefined });

function download(file) {
    window.location.href = file.download_url;
}

function toggleVisibility(file) {
    router.patch(route('files.update', file.id), { visible_to_client: !file.visible_to_client }, options());
}

async function remove(file) {
    if (await confirm({ title: `Delete ${file.name}?`, description: 'The file will be permanently removed for everyone.', confirmLabel: 'Delete file' })) {
        router.delete(route('files.destroy', file.id), options());
    }
}
</script>

<template>
    <EmptyState v-if="!files.length" compact :icon="FolderOpen" :title="emptyTitle" :description="emptyDescription" />
    <ul v-else class="divide-y divide-line">
        <li v-for="file in files" :key="file.id" class="flex items-center gap-3 py-3">
            <FileIcon :kind="file.kind" />
            <div class="min-w-0 flex-1">
                <a :href="file.download_url" class="block truncate text-body font-medium text-ink hover:underline">{{ file.name }}</a>
                <p class="flex flex-wrap items-center gap-x-2 text-caption text-ink-3">
                    <span>{{ formatBytes(file.size) }}</span>
                    <span>·</span>
                    <span>{{ formatRelative(file.created_at) }}</span>
                    <template v-if="showProject && file.project">
                        <span>·</span>
                        <span class="inline-flex items-center gap-1"><span class="size-1.5 rounded-full" :class="swatch(file.project.color)" />{{ file.project.name }}</span>
                    </template>
                </p>
            </div>
            <Badge v-if="file.visible_to_client" tone="warning" size="sm" class="max-sm:hidden">Shared with client</Badge>
            <Avatar v-if="file.uploader" :user="file.uploader" size="sm" class="max-sm:hidden" />
            <Dropdown align="end" label="File actions">
                <template #trigger="{ attrs }">
                    <button type="button" v-bind="attrs" class="rounded-md p-1.5 text-ink-3 hover:bg-hover hover:text-ink" :aria-label="`Actions for ${file.name}`"><MoreHorizontal class="size-4" /></button>
                </template>
                <DropdownItem :icon="Download" @select="download(file)">Download</DropdownItem>
                <template v-if="can('files.manage')">
                    <DropdownItem :icon="file.visible_to_client ? EyeOff : Eye" @select="toggleVisibility(file)">{{ file.visible_to_client ? 'Hide from client' : 'Share with client' }}</DropdownItem>
                    <DropdownSeparator />
                    <DropdownItem :icon="Trash2" danger @select="remove(file)">Delete</DropdownItem>
                </template>
            </Dropdown>
        </li>
    </ul>
</template>
