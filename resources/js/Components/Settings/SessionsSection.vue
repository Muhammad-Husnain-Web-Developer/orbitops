<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { LogOut, Monitor, Smartphone } from '@lucide/vue';
import { computed, ref } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import Modal from '@/Components/UI/Modal.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';
import { formatRelative } from '@/lib/format';

const props = defineProps({
    sessions: { type: Array, required: true },
});

const page = usePage();
const isDemo = computed(() => page.props.auth.user.is_demo);
const others = computed(() => props.sessions.filter((session) => !session.current).length);
const open = ref(false);
const form = useForm({ password: '' });

function submit() {
    form.delete(route('settings.security.sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <SettingsSection title="Browser sessions" description="Where you're signed in. Sign out anywhere you don't recognise.">
        <ul v-if="sessions.length" class="divide-y divide-line">
            <li v-for="session in sessions" :key="session.id" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                <span class="flex size-9 items-center justify-center rounded-lg bg-subtle text-ink-3"><component :is="session.mobile ? Smartphone : Monitor" class="size-4" /></span>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-2 text-body text-ink">{{ session.browser }} on {{ session.platform }} <Badge v-if="session.current" tone="success" size="sm">This device</Badge></p>
                    <p class="text-caption text-ink-3">{{ session.ip ?? 'Unknown IP' }} · {{ session.current ? 'Active now' : `Last active ${formatRelative(session.last_active)}` }}</p>
                </div>
            </li>
        </ul>
        <p v-else class="text-small text-ink-3">Session details aren't available with the current session driver.</p>
        <template #footer>
            <p v-if="isDemo" class="mr-auto text-small text-ink-3">Disabled for the shared demo account.</p>
            <Button variant="secondary" :icon="LogOut" :disabled="isDemo || !others" @click="open = true">Sign out other sessions</Button>
        </template>

        <Modal v-model:open="open" title="Sign out other sessions" :description="`This signs you out on ${others} other ${others === 1 ? 'device' : 'devices'}. Enter your password to confirm.`" size="sm">
            <form id="sessions-form" novalidate @submit.prevent="submit">
                <Field label="Password" :error="form.errors.password" v-slot="{ id, invalid }">
                    <PasswordInput :id="id" v-model="form.password" autocomplete="current-password" :invalid="invalid" />
                </Field>
            </form>
            <template #footer>
                <Button variant="secondary" @click="open = false">Cancel</Button>
                <Button type="submit" form="sessions-form" :loading="form.processing" :disabled="!form.password">Sign out others</Button>
            </template>
        </Modal>
    </SettingsSection>
</template>
