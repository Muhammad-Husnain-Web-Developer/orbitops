<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { BadgeCheck, Circle, CircleCheck, CircleDot, Flag, MoreHorizontal, Plus, ShieldCheck, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Input from '@/Components/UI/Input.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Switch from '@/Components/UI/Switch.vue';
import { confirm } from '@/composables/useConfirm';
import { daysUntil, dueLabel, formatDate } from '@/lib/format';

const props = defineProps({
    projectId: { type: Number, required: true },
    milestones: { type: Array, required: true },
    editable: { type: Boolean, default: false },
});

const adding = ref(false);
const form = useForm({ name: '', due_date: '', requires_approval: false });
const only = ['milestones', 'activity', 'stats'];

const icons = { pending: Circle, in_progress: CircleDot, completed: CircleCheck };
const approval = {
    pending: ['warning', 'Awaiting client approval'],
    approved: ['success', 'Approved by client'],
    changes_requested: ['danger', 'Changes requested'],
};

function add() {
    form.post(route('milestones.store', props.projectId), {
        preserveScroll: true,
        only,
        onSuccess: () => {
            form.reset();
            adding.value = false;
        },
    });
}

function update(milestone, data) {
    router.put(route('milestones.update', milestone.id), data, { preserveScroll: true, only });
}

async function remove(milestone) {
    if (await confirm({ title: `Remove “${milestone.name}”?`, description: 'Tasks in this milestone are kept.', confirmLabel: 'Remove' })) {
        router.delete(route('milestones.destroy', milestone.id), { preserveScroll: true, only });
    }
}
</script>

<template>
    <div>
        <EmptyState v-if="!milestones.length && !adding" :icon="Flag" title="No milestones yet." description="Break the project into phases your client can follow and approve.">
            <Button v-if="editable" :icon="Plus" @click="adding = true">Add Milestone</Button>
        </EmptyState>

        <ol v-else class="relative space-y-3">
            <li v-for="milestone in milestones" :key="milestone.id" class="flex gap-4 rounded-xl border border-line bg-surface p-4 shadow-card">
                <component :is="icons[milestone.status]" class="mt-0.5 size-5 shrink-0" :class="milestone.status === 'completed' ? 'text-success' : milestone.status === 'in_progress' ? 'text-accent-text' : 'text-ink-3'" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-body font-semibold text-ink">{{ milestone.name }}</h3>
                        <Badge v-if="milestone.approval_status" :tone="approval[milestone.approval_status][0]" size="sm" dot>{{ approval[milestone.approval_status][1] }}</Badge>
                        <Badge v-else-if="milestone.requires_approval" size="sm"><ShieldCheck class="size-3" />Needs client sign-off</Badge>
                    </div>
                    <p class="mt-0.5 text-small text-ink-3">
                        <template v-if="milestone.status === 'completed'">Completed {{ formatDate(milestone.completed_at) }}</template>
                        <span v-else-if="milestone.due_date" :class="daysUntil(milestone.due_date) < 0 ? 'text-danger' : ''">{{ dueLabel(milestone.due_date) }} · {{ formatDate(milestone.due_date) }}</span>
                        <template v-else>No due date</template>
                    </p>
                    <p v-if="milestone.approval_note" class="mt-2 rounded-lg bg-subtle px-3 py-2 text-small text-ink-2">“{{ milestone.approval_note }}”</p>
                    <div v-if="milestone.tasks_count" class="mt-3 flex items-center gap-3">
                        <ProgressBar :value="(milestone.completed_tasks_count / milestone.tasks_count) * 100" size="sm" :label="`${milestone.name} progress`" />
                        <span class="shrink-0 text-caption text-ink-3 tabular">{{ milestone.completed_tasks_count }}/{{ milestone.tasks_count }} tasks</span>
                    </div>
                </div>
                <Dropdown v-if="editable" align="end" :label="`Actions for ${milestone.name}`">
                    <template #trigger="{ attrs }">
                        <button type="button" v-bind="attrs" class="h-fit rounded-md p-1.5 text-ink-3 hover:bg-hover hover:text-ink" :aria-label="`Actions for ${milestone.name}`"><MoreHorizontal class="size-4" /></button>
                    </template>
                    <DropdownItem v-if="milestone.status !== 'in_progress' && milestone.status !== 'completed'" :icon="CircleDot" @select="update(milestone, { status: 'in_progress' })">Start milestone</DropdownItem>
                    <DropdownItem v-if="milestone.status !== 'completed'" :icon="BadgeCheck" @select="update(milestone, { status: 'completed' })">Mark complete</DropdownItem>
                    <DropdownItem v-else :icon="Circle" @select="update(milestone, { status: 'in_progress' })">Reopen</DropdownItem>
                    <DropdownItem :icon="ShieldCheck" @select="update(milestone, { requires_approval: !milestone.requires_approval })">{{ milestone.requires_approval ? 'Remove client approval' : 'Require client approval' }}</DropdownItem>
                    <DropdownSeparator />
                    <DropdownItem :icon="Trash2" danger @select="remove(milestone)">Remove</DropdownItem>
                </Dropdown>
            </li>
        </ol>

        <form v-if="adding" class="mt-3 space-y-3 rounded-xl border border-dashed border-line-strong p-4" @submit.prevent="add">
            <div class="grid gap-3 sm:grid-cols-[1fr_12rem]">
                <Input v-model="form.name" autofocus placeholder="Milestone name, e.g. Visual Design" aria-label="Milestone name" :invalid="Boolean(form.errors.name)" />
                <Input v-model="form.due_date" type="date" aria-label="Due date" />
            </div>
            <p v-if="form.errors.name" class="text-small text-danger" role="alert">{{ form.errors.name }}</p>
            <Switch v-model="form.requires_approval" label="Requires client approval" size="sm" />
            <div class="flex justify-end gap-2">
                <Button variant="ghost" size="sm" @click="adding = false">Cancel</Button>
                <Button type="submit" size="sm" :loading="form.processing">Add milestone</Button>
            </div>
        </form>
        <Button v-else-if="editable && milestones.length" variant="ghost" size="sm" :icon="Plus" class="mt-3" @click="adding = true">Add milestone</Button>
    </div>
</template>
