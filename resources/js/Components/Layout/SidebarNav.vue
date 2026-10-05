<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { primaryNav, settingsNav, visibleItems, workspaceNav } from '@/lib/navigation';

defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const { can } = usePermissions();

const primary = computed(() => visibleItems(primaryNav, can));
const secondary = computed(() => visibleItems(workspaceNav, can));
const isActive = (item) => route().current(item.match);
const count = (item) => (item.count ? page.props.counts?.[item.count] ?? 0 : 0);
</script>

<template>
    <nav class="flex flex-1 flex-col gap-6" aria-label="Workspace">
        <ul class="space-y-0.5">
            <li v-for="item in primary" :key="item.route">
                <Link
                    :href="route(item.route)"
                    class="group relative flex h-8 items-center gap-2.5 rounded-lg px-2.5 text-body font-medium transition-colors duration-150"
                    :class="[isActive(item) ? 'bg-accent/10 text-accent-text' : 'text-ink-2 hover:bg-hover hover:text-ink', collapsed ? 'justify-center px-0' : '']"
                    :aria-current="isActive(item) ? 'page' : undefined"
                    :title="collapsed ? item.label : undefined"
                >
                    <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item) ? '' : 'text-ink-3 group-hover:text-ink-2'" aria-hidden="true" />
                    <span v-if="!collapsed" class="flex-1 truncate">{{ item.label }}</span>
                    <span v-else class="sr-only">{{ item.label }}</span>
                    <span
                        v-if="count(item) && !collapsed"
                        class="rounded-full px-1.5 py-px text-[0.6875rem] font-semibold tabular"
                        :class="item.countTone === 'danger' ? 'bg-danger/12 text-danger' : 'bg-subtle text-ink-3'"
                        :aria-label="`${count(item)} items`"
                        >{{ count(item) }}</span
                    >
                    <span v-else-if="count(item) && item.countTone === 'danger'" class="absolute top-1 right-1.5 size-1.5 rounded-full bg-danger" aria-hidden="true" />
                </Link>
            </li>
        </ul>

        <div v-if="secondary.length">
            <p v-if="!collapsed" class="mb-1.5 px-2.5 text-eyebrow text-ink-3 uppercase">Workspace</p>
            <div v-else class="mx-auto mb-2 h-px w-6 bg-line" />
            <ul class="space-y-0.5">
                <li v-for="item in secondary" :key="item.route">
                    <Link
                        :href="route(item.route)"
                        class="group flex h-8 items-center gap-2.5 rounded-lg px-2.5 text-body font-medium transition-colors duration-150"
                        :class="[isActive(item) ? 'bg-accent/10 text-accent-text' : 'text-ink-2 hover:bg-hover hover:text-ink', collapsed ? 'justify-center px-0' : '']"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        :title="collapsed ? item.label : undefined"
                    >
                        <component :is="item.icon" class="size-4 shrink-0" :class="isActive(item) ? '' : 'text-ink-3 group-hover:text-ink-2'" aria-hidden="true" />
                        <span :class="collapsed ? 'sr-only' : 'truncate'">{{ item.label }}</span>
                    </Link>
                </li>
            </ul>
        </div>

        <div class="mt-auto">
            <Link
                :href="route(settingsNav.route)"
                class="group flex h-8 items-center gap-2.5 rounded-lg px-2.5 text-body font-medium transition-colors"
                :class="[isActive(settingsNav) ? 'bg-accent/10 text-accent-text' : 'text-ink-2 hover:bg-hover hover:text-ink', collapsed ? 'justify-center px-0' : '']"
                :aria-current="isActive(settingsNav) ? 'page' : undefined"
                :title="collapsed ? settingsNav.label : undefined"
            >
                <component :is="settingsNav.icon" class="size-4 shrink-0" :class="isActive(settingsNav) ? '' : 'text-ink-3 group-hover:text-ink-2'" aria-hidden="true" />
                <span :class="collapsed ? 'sr-only' : ''">{{ settingsNav.label }}</span>
            </Link>
        </div>
    </nav>
</template>
