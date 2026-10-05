<script setup>
import { router } from '@inertiajs/vue3';
import { Briefcase, CheckSquare, Clock, FileText, FolderKanban, Plus } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import { usePermissions } from '@/composables/usePermissions';
import { openQuickCreate } from '@/composables/useQuickCreate';

defineProps({
    compact: { type: Boolean, default: false },
});

const { can } = usePermissions();

const items = computed(() =>
    [
        { label: 'New client', icon: Briefcase, permission: 'clients.manage', run: () => openQuickCreate('client'), shortcut: 'C' },
        { label: 'New project', icon: FolderKanban, permission: 'projects.manage', run: () => openQuickCreate('project'), shortcut: 'P' },
        { label: 'New task', icon: CheckSquare, permission: 'tasks.manage', run: () => openQuickCreate('task'), shortcut: 'T' },
        { label: 'New invoice', icon: FileText, permission: 'invoices.manage', run: () => router.visit(route('invoices.create')), shortcut: 'I' },
        { label: 'Track time', icon: Clock, permission: 'time.track', run: () => openQuickCreate('time'), shortcut: 'L' },
    ].filter((item) => can(item.permission)),
);
</script>

<template>
    <Dropdown v-if="items.length" align="end" width="w-56" label="Quick create">
        <template #trigger="{ attrs }">
            <Button v-if="!compact" v-bind="attrs" size="sm" :icon="Plus">New</Button>
            <button v-else type="button" v-bind="attrs" class="flex size-12 items-center justify-center rounded-2xl bg-accent text-accent-ink shadow-raised transition-transform active:scale-95" aria-label="Quick create">
                <Plus class="size-5" />
            </button>
        </template>
        <DropdownItem v-for="item in items" :key="item.label" :icon="item.icon" :shortcut="item.shortcut" @select="item.run">{{ item.label }}</DropdownItem>
    </Dropdown>
</template>
