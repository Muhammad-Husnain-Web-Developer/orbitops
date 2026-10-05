<script setup>
import { Head } from '@inertiajs/vue3';
import PasswordForm from '@/Components/Settings/PasswordForm.vue';
import SessionsSection from '@/Components/Settings/SessionsSection.vue';
import TwoFactorSection from '@/Components/Settings/TwoFactorSection.vue';

defineProps({
    two_factor: { type: Object, required: true },
    sessions: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Security" />
    <div class="space-y-6">
        <PasswordForm />
        <TwoFactorSection :two-factor="two_factor" />
        <SessionsSection :sessions="sessions" />
    </div>
</template>
