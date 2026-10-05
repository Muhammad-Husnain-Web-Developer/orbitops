<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { Lock, RotateCcw } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import { confirm } from '@/composables/useConfirm';

const props = defineProps({
    roles: { type: Array, required: true },
    groups: { type: Array, required: true },
});

const page = usePage();
const editable = computed(() => props.roles.filter((role) => role.editable));
const selected = ref(editable.value[0]?.name ?? props.roles[0]?.name);
const role = computed(() => props.roles.find((item) => item.name === selected.value));

// Working copy per role so switching tabs keeps unsaved changes.
const drafts = reactive(Object.fromEntries(props.roles.map((item) => [item.name, [...item.permissions]])));
const saving = ref(false);

const dirty = computed(() => {
    const original = [...(role.value?.permissions ?? [])].sort().join();
    return [...drafts[selected.value]].sort().join() !== original;
});

const roleOptions = computed(() => props.roles.map((item) => ({ value: item.name, label: item.label })));
const isMine = computed(() => page.props.access?.role === selected.value);

function toggleGroup(group, on) {
    const names = group.permissions.map((permission) => permission.name);
    drafts[selected.value] = on ? [...new Set([...drafts[selected.value], ...names])] : drafts[selected.value].filter((name) => !names.includes(name));
}

const groupState = (group) => {
    const count = group.permissions.filter((permission) => drafts[selected.value].includes(permission.name)).length;
    return count === 0 ? 'none' : count === group.permissions.length ? 'all' : 'some';
};

function resetDefaults() {
    drafts[selected.value] = [...role.value.defaults];
}

async function save() {
    if (isMine.value && !drafts[selected.value].includes('workspace.roles')) {
        const ok = await confirm({ title: 'Remove your own access to this page?', description: 'You have this role. Without “Manage roles & permissions” you won’t be able to undo this yourself.', confirmLabel: 'Save anyway' });
        if (!ok) return;
    }

    router.put(route('settings.roles.update', role.value.id), { permissions: drafts[selected.value] }, {
        preserveScroll: true,
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
    });
}
</script>

<template>
    <Head title="Roles & permissions" />
    <SettingsSection title="Roles & permissions" description="Decide what each role can see and do. Changes apply to everyone with the role, immediately. The server enforces every permission.">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="no-scrollbar -mx-1 overflow-x-auto px-1">
                <SegmentedControl v-model="selected" :options="roleOptions" label="Role" />
            </div>
            <p class="text-small text-ink-3">{{ role.members }} {{ role.members === 1 ? 'person has' : 'people have' }} this role</p>
        </div>

        <p class="mb-4 text-small text-ink-2">{{ role.description }}</p>

        <div v-if="!role.editable" class="mb-4 flex items-center gap-2 rounded-lg bg-subtle px-3 py-2.5 text-small text-ink-2">
            <Lock class="size-4 shrink-0 text-ink-3" />
            {{ role.name === 'owner' ? 'The owner always has every permission.' : 'Clients only ever see their own client portal.' }}
        </div>

        <div class="space-y-4">
            <fieldset v-for="group in groups" :key="group.key" class="rounded-xl border border-line" :disabled="!role.editable">
                <legend class="sr-only">{{ group.label }}</legend>
                <div class="flex items-center justify-between gap-3 border-b border-line bg-canvas/40 px-4 py-2.5">
                    <p class="text-body font-medium text-ink">{{ group.label }}</p>
                    <Badge :tone="groupState(group) === 'all' ? 'success' : groupState(group) === 'some' ? 'info' : 'neutral'" size="sm">
                        {{ groupState(group) === 'all' ? 'Full access' : groupState(group) === 'some' ? 'Partial' : 'No access' }}
                    </Badge>
                </div>
                <div class="grid gap-x-6 gap-y-2.5 px-4 py-3 sm:grid-cols-2">
                    <Checkbox v-for="permission in group.permissions" :key="permission.name" v-model="drafts[selected]" :value="permission.name" :label="permission.label" :disabled="!role.editable" />
                </div>
                <div v-if="role.editable" class="flex gap-3 border-t border-line px-4 py-2 text-caption">
                    <button type="button" class="font-medium text-accent-text hover:underline" @click="toggleGroup(group, true)">Select all</button>
                    <button type="button" class="font-medium text-ink-3 hover:text-ink hover:underline" @click="toggleGroup(group, false)">Clear</button>
                </div>
            </fieldset>
        </div>

        <template v-if="role.editable" #footer>
            <Button variant="ghost" :icon="RotateCcw" class="mr-auto" @click="resetDefaults">Reset to defaults</Button>
            <Button :loading="saving" :disabled="!dirty" @click="save">Save {{ role.label }} permissions</Button>
        </template>
    </SettingsSection>
</template>
