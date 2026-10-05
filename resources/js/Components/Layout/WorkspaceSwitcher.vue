<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownLabel from '@/Components/UI/DropdownLabel.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import { swatch } from '@/lib/colors';
import WorkspaceFormModal from './WorkspaceFormModal.vue';

defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const workspace = computed(() => page.props.workspace);
const creating = ref(false);

function switchTo(item) {
    if (item.id !== workspace.value.id) {
        router.put(route('workspaces.switch', item.id));
    }
}
</script>

<template>
    <Dropdown width="w-72" label="Switch workspace">
        <template #trigger="{ attrs, open }">
            <button
                type="button"
                v-bind="attrs"
                class="group flex w-full items-center gap-2.5 rounded-lg border border-transparent p-1.5 text-left transition-colors hover:border-line hover:bg-hover"
                :class="[open ? 'border-line bg-hover' : '', collapsed ? 'justify-center' : '']"
                :aria-label="`Current workspace: ${workspace.name}. Switch workspace`"
            >
                <span class="relative flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg text-caption font-bold text-white shadow-card" :class="workspace.logo_url ? '' : swatch(workspace.accent)">
                    <img v-if="workspace.logo_url" :src="workspace.logo_url" alt="" class="size-full object-cover" />
                    <template v-else>{{ workspace.initials }}</template>
                </span>
                <span v-if="!collapsed" class="min-w-0 flex-1">
                    <span class="block truncate text-body font-semibold text-ink">{{ workspace.name }}</span>
                    <span class="block truncate text-caption text-ink-3">{{ workspace.industry ?? 'Workspace' }}</span>
                </span>
                <ChevronsUpDown v-if="!collapsed" class="size-4 shrink-0 text-ink-3 group-hover:text-ink-2" aria-hidden="true" />
            </button>
        </template>

        <DropdownLabel>Workspaces</DropdownLabel>
        <DropdownItem v-for="item in page.props.workspaces" :key="item.id" :active="item.id === workspace.id" @select="switchTo(item)">
            <span class="flex items-center gap-2.5">
                <span class="flex size-6 shrink-0 items-center justify-center rounded-md text-[0.625rem] font-bold text-white" :class="swatch(item.accent)">{{ item.initials }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium">{{ item.name }}</span>
                    <span class="block truncate text-caption text-ink-3">{{ item.industry ?? 'Workspace' }}</span>
                </span>
            </span>
            <template #end>
                <Badge v-if="item.role" size="sm" :tone="item.role === 'owner' ? 'accent' : 'neutral'">{{ item.role }}</Badge>
                <Check v-if="item.id === workspace.id" class="size-4 text-accent-text" aria-label="Current workspace" />
            </template>
        </DropdownItem>
        <DropdownSeparator />
        <DropdownItem :icon="Plus" @select="creating = true">Create workspace</DropdownItem>
    </Dropdown>
    <WorkspaceFormModal v-model:open="creating" />
</template>
