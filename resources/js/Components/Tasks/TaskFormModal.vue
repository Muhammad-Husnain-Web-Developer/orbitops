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
import Textarea from '@/Components/UI/Textarea.vue';
import { useEnums } from '@/composables/useEnums';
import { useLookups } from '@/composables/useLookups';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    defaults: { type: Object, default: () => ({}) },
    milestones: { type: Array, default: () => [] },
});

const page = usePage();
const { options } = useEnums();
const { lookups, load } = useLookups();

const blank = () => ({
    title: '',
    project_id: '',
    milestone_id: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    assignee_id: page.props.auth.user.id,
    due_date: '',
    estimate_hours: '',
    visible_to_client: false,
});

const form = useForm(blank());

watch(open, (value) => {
    if (!value) return;
    load(page.props.workspace.id);
    form.clearErrors();
    form.defaults({ ...blank(), ...props.defaults });
    form.reset();
});

const projectOptions = computed(() =>
    lookups.projects.filter((project) => !['completed', 'cancelled'].includes(project.status)).map((project) => ({ value: project.id, label: project.client ? `${project.name} · ${project.client}` : project.name })),
);
const memberOptions = computed(() => lookups.members.map((member) => ({ value: member.id, label: member.name })));
const milestoneOptions = computed(() => props.milestones.map((milestone) => ({ value: milestone.id, label: milestone.name })));

function submit(another = false) {
    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            if (another) {
                form.reset('title', 'description');
            } else {
                open.value = false;
            }
        },
    });
}
</script>

<template>
    <Modal v-model:open="open" title="New task" size="lg">
        <form id="task-form" class="grid gap-4 sm:grid-cols-6" novalidate @submit.prevent="submit(false)">
            <Field label="Title" :error="form.errors.title" required class="sm:col-span-6" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.title" autofocus :invalid="invalid" placeholder="Design the pricing page" />
            </Field>
            <Field label="Project" :error="form.errors.project_id" required class="sm:col-span-3" v-slot="{ id, invalid }">
                <Skeleton v-if="!lookups.loaded" class="h-9" />
                <Select v-else :id="id" v-model="form.project_id" :options="projectOptions" placeholder="Choose a project…" :invalid="invalid" />
            </Field>
            <Field label="Assignee" :error="form.errors.assignee_id" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Skeleton v-if="!lookups.loaded" class="h-9" />
                <Select v-else :id="id" v-model="form.assignee_id" :options="memberOptions" placeholder="Unassigned" :invalid="invalid" />
            </Field>
            <Field label="Description" :error="form.errors.description" class="sm:col-span-6" v-slot="{ id, invalid }">
                <Textarea :id="id" v-model="form.description" :rows="3" autosize :invalid="invalid" placeholder="Add context, links or acceptance criteria…" />
            </Field>
            <Field label="Status" :error="form.errors.status" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.status" :options="options('taskStatus')" :invalid="invalid" />
            </Field>
            <Field label="Priority" :error="form.errors.priority" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.priority" :options="options('taskPriority')" :invalid="invalid" />
            </Field>
            <Field label="Due date" :error="form.errors.due_date" class="sm:col-span-2" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.due_date" type="date" :invalid="invalid" />
            </Field>
            <Field v-if="milestoneOptions.length" label="Milestone" :error="form.errors.milestone_id" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.milestone_id" :options="milestoneOptions" placeholder="None" :invalid="invalid" />
            </Field>
            <Field label="Estimate" :error="form.errors.estimate_hours" class="sm:col-span-3" v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.estimate_hours" type="number" min="0" step="0.5" suffix="hours" :invalid="invalid" />
            </Field>
            <div class="sm:col-span-6">
                <Switch v-model="form.visible_to_client" label="Visible to client" description="Show this task in the client portal." />
            </div>
        </form>
        <template #footer>
            <Button variant="ghost" class="sm:mr-auto" :disabled="form.processing" @click="submit(true)">Create &amp; add another</Button>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="task-form" :loading="form.processing">Create task</Button>
        </template>
    </Modal>
</template>
