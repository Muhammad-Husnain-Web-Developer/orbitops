<script setup>
import { Head } from '@inertiajs/vue3';
import ThemePicker from '@/Components/Settings/ThemePicker.vue';
</script>

<template>
    <Head title="Appearance" />
    <ThemePicker />
    <p class="mt-4 text-small text-ink-3">The accent colour is set per workspace under Workspace settings, so everyone in a workspace sees the same brand colour.</p>
</template>
