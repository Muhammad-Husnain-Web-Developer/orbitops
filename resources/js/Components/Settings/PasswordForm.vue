<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Button from '@/Components/UI/Button.vue';
import Field from '@/Components/UI/Field.vue';
import PasswordInput from '@/Components/UI/PasswordInput.vue';
import { toast } from '@/composables/useToast';

const page = usePage();
const isDemo = computed(() => page.props.auth.user.is_demo);
const form = useForm({ current_password: '', password: '', password_confirmation: '' });

function submit() {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (!isDemo.value) toast.success('Password updated');
        },
        onError: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <SettingsSection title="Password" description="Use at least 8 characters. A passphrase is easier to remember and harder to guess.">
        <form id="password-form" class="grid gap-4 sm:grid-cols-2" novalidate @submit.prevent="submit">
            <Field label="Current password" :error="form.errors.current_password" class="sm:col-span-2" v-slot="{ id, invalid }">
                <PasswordInput :id="id" v-model="form.current_password" autocomplete="current-password" :invalid="invalid" />
            </Field>
            <Field label="New password" :error="form.errors.password" v-slot="{ id, invalid }">
                <PasswordInput :id="id" v-model="form.password" autocomplete="new-password" strength :invalid="invalid" />
            </Field>
            <Field label="Confirm new password" :error="form.errors.password_confirmation" v-slot="{ id, invalid }">
                <PasswordInput :id="id" v-model="form.password_confirmation" autocomplete="new-password" :invalid="invalid" />
            </Field>
        </form>
        <template #footer>
            <p v-if="isDemo" class="mr-auto text-small text-ink-3">Disabled for the shared demo account.</p>
            <Button type="submit" form="password-form" :loading="form.processing" :disabled="isDemo || !form.current_password || !form.password">Update password</Button>
        </template>
    </SettingsSection>
</template>
