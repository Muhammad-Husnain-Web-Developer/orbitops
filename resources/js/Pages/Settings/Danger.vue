<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';
import Select from '@/Components/UI/Select.vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    isOwner: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    otherWorkspaces: { type: Number, default: 0 },
    candidates: { type: Array, default: () => [] },
});

const action = ref(null); // 'leave' | 'transfer' | 'delete'
const form = useForm({ password: '', user_id: '', name: '' });
const candidateOptions = computed(() => props.candidates.map((user) => ({ value: user.id, label: `${user.name} · ${user.email}` })));
const open = computed({ get: () => Boolean(action.value), set: (value) => !value && (action.value = null) });

function start(kind) {
    form.reset();
    form.clearErrors();
    action.value = kind;
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => (action.value = null) };

    if (action.value === 'leave') form.post(route('settings.danger.leave'), options);
    if (action.value === 'transfer') form.post(route('settings.danger.transfer'), options);
    if (action.value === 'delete') form.delete(route('settings.danger.destroy'), options);
}

const copy = computed(() => ({
    leave: { title: `Leave ${props.workspace.name}?`, confirm: 'Leave workspace', description: 'You lose access straight away. Your open tasks become unassigned; time you tracked is kept.' },
    transfer: { title: 'Transfer ownership', confirm: 'Transfer ownership', description: 'The new owner gets full control, including billing and deletion. You become an admin.' },
    delete: { title: `Delete ${props.workspace.name}?`, confirm: 'Delete workspace forever', description: 'Every client, project, task, invoice, expense and file in this workspace is permanently deleted. This cannot be undone.' },
})[action.value] ?? {});
</script>

<template>
    <Head title="Danger zone" />
    <div class="space-y-6">
        <SettingsSection v-if="!isOwner" title="Leave workspace" description="Remove yourself from this workspace." danger>
            <p class="text-small text-ink-2">{{ otherWorkspaces ? 'You will be moved to another workspace you belong to.' : 'This is your only workspace, so you will be asked to create a new one.' }}</p>
            <template #footer><Button variant="danger" @click="start('leave')">Leave workspace</Button></template>
        </SettingsSection>

        <SettingsSection v-if="isOwner" title="Transfer ownership" description="Hand the workspace to another team member." danger>
            <p class="text-small text-ink-2">{{ candidates.length ? 'Owners can’t leave their workspace. Transfer it first if someone else should run it.' : 'Invite a teammate first: ownership can only go to an active team member.' }}</p>
            <template #footer><Button variant="danger" :disabled="!candidates.length" @click="start('transfer')">Transfer ownership</Button></template>
        </SettingsSection>

        <SettingsSection v-if="isOwner" title="Delete workspace" description="Permanently remove the workspace and everything in it." danger>
            <p class="text-small text-ink-2">Team members and client portal users lose access immediately. Export reports first if you need records.</p>
            <template #footer><Button variant="danger" :disabled="!canDelete" @click="start('delete')">Delete workspace</Button></template>
        </SettingsSection>
    </div>

    <Modal v-model:open="open" :title="copy.title" :description="copy.description" size="sm">
        <form id="danger-form" class="space-y-4" novalidate @submit.prevent="submit">
            <Field v-if="action === 'transfer'" label="New owner" :error="form.errors.user_id" required v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.user_id" :options="candidateOptions" placeholder="Choose a team member" :invalid="invalid" />
            </Field>
            <Field v-if="action === 'delete'" :label="`Type “${workspace.name}” to confirm`" :error="form.errors.name" required v-slot="{ id, invalid }">
                <Input :id="id" v-model="form.name" autocomplete="off" :invalid="invalid" />
            </Field>
            <Field label="Your password" :error="form.errors.password" required v-slot="{ id, invalid }">
                <PasswordInput :id="id" v-model="form.password" autocomplete="current-password" :invalid="invalid" />
            </Field>
        </form>
        <template #footer>
            <Button variant="secondary" @click="action = null">Cancel</Button>
            <Button type="submit" form="danger-form" variant="danger" :loading="form.processing" :disabled="!form.password || (action === 'delete' && form.name !== workspace.name) || (action === 'transfer' && !form.user_id)">{{ copy.confirm }}</Button>
        </template>
    </Modal>
</template>
