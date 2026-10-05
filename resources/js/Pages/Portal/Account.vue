<script setup>
import { Head } from '@inertiajs/vue3';
import PasswordForm from '@/Components/Settings/PasswordForm.vue';
import ProfileForm from '@/Components/Settings/ProfileForm.vue';
import SessionsSection from '@/Components/Settings/SessionsSection.vue';
import ThemePicker from '@/Components/Settings/ThemePicker.vue';
import TwoFactorSection from '@/Components/Settings/TwoFactorSection.vue';

defineProps({
    profile: { type: Object, required: true },
    timezones: { type: Array, default: () => [] },
    two_factor: { type: Object, required: true },
    sessions: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Your account" />
    <header class="mx-auto mb-6 max-w-3xl">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">Your account</h1>
        <p class="mt-1 text-body text-ink-3">Your profile, sign-in security and how the portal looks.</p>
    </header>
    <div class="mx-auto max-w-3xl space-y-6">
        <ProfileForm :profile="profile" :timezones="timezones" :show-title="false" />
        <PasswordForm />
        <TwoFactorSection :two-factor="two_factor" />
        <SessionsSection :sessions="sessions" />
        <ThemePicker />
    </div>
</template>
