<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Bell, Building2, Code2, CreditCard, KeyRound, Palette, Plug, Shield, User, Users } from '@lucide/vue';
import { computed } from 'vue';
import Select from '@/Components/UI/Select.vue';
import { usePermissions } from '@/composables/usePermissions';

const page = usePage();
const { can } = usePermissions();

const groups = computed(() => [
    {
        label: 'Account',
        items: [
            { label: 'General', route: 'settings.general', icon: User },
            { label: 'Notifications', route: 'settings.notifications', icon: Bell },
            { label: 'Security', route: 'settings.security', icon: Shield },
            { label: 'Appearance', route: 'settings.appearance', icon: Palette },
        ],
    },
    {
        label: 'Workspace',
        items: [
            { label: 'Workspace', route: 'settings.workspace', icon: Building2, permission: 'workspace.settings' },
            { label: 'Members', route: 'team.index', icon: Users, permission: 'team.view' },
            { label: 'Roles & permissions', route: 'settings.roles', icon: KeyRound, permission: 'workspace.roles' },
            { label: 'Billing', route: 'settings.billing', icon: CreditCard, permission: 'workspace.billing' },
            { label: 'Integrations', route: 'settings.integrations', icon: Plug, permission: 'workspace.settings' },
            { label: 'API', route: 'settings.api', icon: Code2 },
            { label: 'Danger zone', route: 'settings.danger', icon: AlertTriangle, danger: true },
        ].filter((item) => !item.permission || can(item.permission)),
    },
]);

const items = computed(() => groups.value.flatMap((group) => group.items));
const current = computed(() => items.value.find((item) => route().current(item.route))?.route ?? '');
const mobileOptions = computed(() => groups.value.flatMap((group) => group.items.map((item) => ({ value: item.route, label: `${group.label} · ${item.label}` }))));
</script>

<template>
    <div>
        <header class="mb-6 sm:mb-8">
            <h1 class="text-h2 text-ink sm:text-[1.75rem] sm:leading-tight sm:tracking-[-0.03em]">Settings</h1>
            <p class="mt-1 text-body text-ink-3">Your account and the {{ page.props.workspace?.name }} workspace.</p>
        </header>

        <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
            <div class="lg:hidden">
                <Select :model-value="current" :options="mobileOptions" aria-label="Settings section" @update:model-value="(name) => router.visit(route(name))" />
            </div>

            <nav class="hidden lg:block" aria-label="Settings">
                <div class="sticky top-20 space-y-6">
                    <div v-for="group in groups" :key="group.label">
                        <p class="mb-1.5 px-3 text-caption font-medium tracking-wide text-ink-3 uppercase">{{ group.label }}</p>
                        <ul class="space-y-0.5">
                            <li v-for="item in group.items" :key="item.route">
                                <Link
                                    :href="route(item.route)"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-body transition-colors"
                                    :class="current === item.route ? 'bg-hover font-medium text-ink' : item.danger ? 'text-danger hover:bg-danger/8' : 'text-ink-2 hover:bg-hover hover:text-ink'"
                                    :aria-current="current === item.route ? 'page' : undefined"
                                >
                                    <component :is="item.icon" class="size-4" :class="current === item.route ? 'text-accent-text' : ''" aria-hidden="true" />
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="min-w-0 max-w-3xl">
                <slot />
            </div>
        </div>
    </div>
</template>
