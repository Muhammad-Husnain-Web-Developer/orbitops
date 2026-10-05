<script setup>
import { Link } from '@inertiajs/vue3';
import { CheckSquare, Clock, FolderKanban, LayoutGrid } from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import QuickCreateMenu from './QuickCreateMenu.vue';

const { can } = usePermissions();

const items = computed(() =>
    [
        { label: 'Home', route: 'dashboard', match: 'dashboard', icon: LayoutGrid },
        { label: 'Projects', route: 'projects.index', match: 'projects.*', icon: FolderKanban, permission: 'projects.view' },
        { label: 'Tasks', route: 'tasks.index', match: 'tasks.*', icon: CheckSquare, permission: 'tasks.view' },
        { label: 'Time', route: 'time.index', match: 'time.*', icon: Clock, permission: 'time.track' },
    ].filter((item) => !item.permission || can(item.permission)),
);

const left = computed(() => items.value.slice(0, 2));
const right = computed(() => items.value.slice(2));
</script>

<template>
    <nav class="safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-line bg-canvas/90 px-2 pt-1.5 backdrop-blur-xl lg:hidden" aria-label="Primary">
        <ul class="grid grid-cols-5 items-center">
            <li v-for="item in left" :key="item.route">
                <Link :href="route(item.route)" class="flex flex-col items-center gap-0.5 rounded-lg py-1.5 text-[0.6875rem] font-medium" :class="route().current(item.match) ? 'text-accent-text' : 'text-ink-3'" :aria-current="route().current(item.match) ? 'page' : undefined">
                    <component :is="item.icon" class="size-5" aria-hidden="true" />{{ item.label }}
                </Link>
            </li>
            <li class="flex justify-center"><QuickCreateMenu compact /></li>
            <li v-for="item in right" :key="item.route">
                <Link :href="route(item.route)" class="flex flex-col items-center gap-0.5 rounded-lg py-1.5 text-[0.6875rem] font-medium" :class="route().current(item.match) ? 'text-accent-text' : 'text-ink-3'" :aria-current="route().current(item.match) ? 'page' : undefined">
                    <component :is="item.icon" class="size-5" aria-hidden="true" />{{ item.label }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
