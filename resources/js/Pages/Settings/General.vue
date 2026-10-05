<script setup>
import { Head } from '@inertiajs/vue3';
import ProfileForm from '@/Components/Settings/ProfileForm.vue';

defineProps({
    profile: { type: Object, required: true },
    timezones: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="General settings" />
    <ProfileForm :profile="profile" :timezones="timezones" />
</template>
