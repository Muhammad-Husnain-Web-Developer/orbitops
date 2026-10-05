<script setup>
import { useForm } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed, watch } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Modal from '@/Components/UI/Modal.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    roles: { type: Array, required: true },
    clients: { type: Array, default: () => [] },
    seats: { type: Object, required: true },
});

const form = useForm({ emails: '', role: 'member', client_id: '' });

watch(open, (value) => {
    if (!value) return;
    form.reset();
    form.clearErrors();
});

const choices = computed(() => [
    ...props.roles,
    { value: 'client', label: 'Client', description: 'Sees only their own projects, files, invoices and approvals in the client portal.' },
]);
const clientOptions = computed(() => props.clients.map((client) => ({ value: client.id, label: client.name })));
const count = computed(() => form.emails.split(/[\s,;]+/).filter((email) => email.includes('@')).length);
const seatsLeft = computed(() => (props.seats.limit === null ? null : Math.max(0, props.seats.limit - props.seats.used)));
const emailError = computed(() => form.errors.emails ?? Object.entries(form.errors).find(([key]) => key.startsWith('emails.'))?.[1]);

function submit() {
    form.post(route('team.invitations.store'), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Modal v-model:open="open" title="Invite people" description="They'll get an email with a link to join. Invitations expire after 7 days." size="lg">
        <form id="invite-form" class="space-y-5" novalidate @submit.prevent="submit">
            <Field label="Email addresses" :error="emailError" hint="Separate several addresses with commas or new lines (up to 10)." required v-slot="{ id, invalid }">
                <Textarea :id="id" v-model="form.emails" :rows="3" autosize placeholder="olivia@studio.com, marco@studio.com" :invalid="invalid" />
            </Field>

            <fieldset>
                <legend class="mb-2 text-label text-ink-2">Role</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="role in choices"
                        :key="role.value"
                        class="relative flex cursor-pointer gap-3 rounded-xl border p-3 transition-colors has-focus-visible:outline-2 has-focus-visible:outline-accent"
                        :class="form.role === role.value ? 'border-accent bg-accent/5' : 'border-line hover:border-line-strong'"
                    >
                        <input v-model="form.role" type="radio" name="role" :value="role.value" class="sr-only" />
                        <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border" :class="form.role === role.value ? 'border-accent bg-accent text-accent-ink' : 'border-line-strong'">
                            <Check v-if="form.role === role.value" class="size-2.5" stroke-width="3" />
                        </span>
                        <span>
                            <span class="block text-body font-medium text-ink">{{ role.label }}</span>
                            <span class="block text-small text-ink-3">{{ role.description }}</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <Field v-if="form.role === 'client'" label="Client" :error="form.errors.client_id" required v-slot="{ id, invalid }">
                <Select :id="id" v-model="form.client_id" :options="clientOptions" placeholder="Which client do they work for?" :invalid="invalid" />
            </Field>

            <p v-if="form.role !== 'client' && seatsLeft !== null" class="text-small" :class="count > seatsLeft ? 'text-danger' : 'text-ink-3'">
                {{ seatsLeft }} of {{ seats.limit }} team seats left on the {{ seats.plan }} plan. Client portal users don't use a seat.
            </p>
        </form>
        <template #footer>
            <Button variant="secondary" @click="open = false">Cancel</Button>
            <Button type="submit" form="invite-form" :loading="form.processing">{{ count > 1 ? `Send ${count} invitations` : 'Send invitation' }}</Button>
        </template>
    </Modal>
</template>
