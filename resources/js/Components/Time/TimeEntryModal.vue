<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import Select from '@/Components/UI/Select.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import Switch from '@/Components/UI/Switch.vue';
import { useLookups } from '@/composables/useLookups';
import { useProjectTasks } from '@/composables/useProjectTasks';
import { formatDuration, toDate, toDateInput } from '@/lib/format';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    entry: { type: Object, default: null },
    defaults: { type: Object, default: () => ({}) },
});

const page = usePage();
const { lookups, load } = useLookups();

const pad = (n) => String(n).padStart(2, '0');
const timeOf = (iso) => {
    const d = toDate(iso);
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const blank = () => ({ project_id: '', task_id: '', description: '', date: toDateInput(), start: '09:00', end: '10:00', billable: true });
const form = useForm(blank());

watch(open, (value) => {
    if (!value) return;
    load(page.props.workspace.id);
    form.clearErrors();

    if (props.entry) {
        form.defaults({
            project_id: props.entry.project_id,
            task_id: props.entry.task_id ?? '',
            description: props.entry.description ?? '',
            date: toDateInput(props.entry.started_at),
            start: timeOf(props.entry.started_at),
            end: props.entry.ended_at ? timeOf(props.entry.ended_at) : timeOf(new Date().toISOString()),
            billable: props.entry.billable,
        });
    } else {
        form.defaults({ ...blank(), ...props.defaults });
    }

    form.reset();
});

const duration = computed(() => {
    const [sh, sm] = form.start.split(':').map(Number);
    const [eh, em] = form.end.split(':').map(Number);
    const minutes = eh * 60 + em - (sh * 60 + sm);

    return minutes > 0 ? formatDuration(minutes * 60) : null;
});

const { tasks, loading: tasksLoading } = useProjectTasks(() => form.project_id);
const taskOptions = computed(() => tasks.value.map((task) => ({ value: task.id, label: task.done ? `${task.title} (done)` : task.title })));

const projectOptions = computed(() => lookups.projects.map((project) => ({ value: project.id, label: project.client ? `${project.name} · ${project.client}` : project.name })));

function submit() {
    const config = { preserveScroll: true, onSuccess: () => (open.value = false) };
    props.entry ? form.put(route('time.update', props.entry.id), config) : form.post(route('time.store'), config);
}
</script>

<template>
    <Modal v-model:open="open" :title="entry ? 'Edit time entry' : 'Log time'" :description="entry ? null : 'Add time you worked but did not track with the timer.'">
        <form id="time-form" class="grid gap-4 sm:grid-cols-6" novalidate @submit.prevent="submit">
            <Field label="Project" :error="form.errors.project_id" required class="sm:col-span-6" v-slot="{ id, invalid }">
                <Skeleton v-if="!lookups.loaded" class="h-9" />
                <Select v-else :id="id" v-model="form.project_id" :options="projectOptions" placeholder="Choose a project…" :invalid="invalid" @update:model-value="form.task_id = ''" />
            </Field>
            <Field label="Task" :error="form.errors.task_id" hint="Optional" class="sm:col-span-6" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.task_id" :options="taskOptions" :placeholder="!form.project_id ? 'Choose a project first' : tasksLoading ? 'Loading tasks…' : 'No specific task'" :disabled="!form.project_id" :invalid="invalid" />
            </Field>
            <Field label="What did you work on?" :error="form.errors.description" class="sm:col-span-6" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.description" :invalid="invalid" placeholder="Homepage build" />
            </Field>
            <Field label="Date" :error="form.errors.date" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.date" type="date" :invalid="invalid" />
            </Field>
            <Field label="Start" :error="form.errors.start" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.start" type="time" :invalid="invalid" />
            </Field>
            <Field label="End" :error="form.errors.end" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.end" type="time" :invalid="invalid" />
            </Field>
            <p class="text-small text-ink-3 sm:col-span-6" aria-live="polite">
                Duration: <span class="font-medium text-ink tabular">{{ duration ?? 'End must be after start' }}</span>
            </p>
            <div class="sm:col-span-6">
                <Switch v-model="form.billable" label="Billable" description="Billable time can be added to invoices." />
            </div>
        </form>
        <template #footer>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="time-form" :loading="form.processing" :disabled="!duration">{{ entry ? 'Save changes' : 'Log time' }}</Button>
        </template>
    </Modal>
</template>
