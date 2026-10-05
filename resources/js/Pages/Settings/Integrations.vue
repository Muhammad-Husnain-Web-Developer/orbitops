<script setup>
import { Head } from '@inertiajs/vue3';
import { Blocks, Calendar, CreditCard, GitBranch, Hash, PenTool, Webhook, Workflow } from '@lucide/vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import Badge from '@/Components/UI/Badge.vue';

defineProps({
    integrations: { type: Array, required: true },
});

const icons = { slack: Hash, 'google-calendar': Calendar, stripe: CreditCard, quickbooks: Blocks, github: GitBranch, figma: PenTool, zapier: Workflow, webhooks: Webhook };
</script>

<template>
    <Head title="Integrations" />
    <SettingsSection title="Integrations" description="Connect the tools your team already uses. These are on the roadmap and not yet available in this build.">
        <ul class="grid gap-3 sm:grid-cols-2">
            <li v-for="integration in integrations" :key="integration.key" class="flex gap-3 rounded-xl border border-line p-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-subtle text-ink-2"><component :is="icons[integration.key] ?? Blocks" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-body font-medium text-ink">{{ integration.name }}</p>
                        <Badge size="sm">Coming soon</Badge>
                    </div>
                    <p class="text-caption text-ink-3">{{ integration.category }}</p>
                    <p class="mt-1.5 text-small text-ink-2">{{ integration.description }}</p>
                </div>
            </li>
        </ul>
    </SettingsSection>
</template>
