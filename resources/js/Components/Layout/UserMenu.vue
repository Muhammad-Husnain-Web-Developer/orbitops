<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { BookOpen, Check, Globe, Keyboard, LogOut, Monitor, Moon, Settings, Sun } from '@lucide/vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import DropdownLabel from '@/Components/UI/DropdownLabel.vue';
import DropdownSeparator from '@/Components/UI/DropdownSeparator.vue';
import { useTheme } from '@/composables/useTheme';

const emit = defineEmits(['shortcuts']);

const page = usePage();
const { preference, setTheme } = useTheme();

const themes = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <Dropdown align="end" width="w-64" label="Account">
        <template #trigger="{ attrs }">
            <button type="button" v-bind="attrs" class="rounded-full ring-offset-2 ring-offset-canvas transition hover:ring-2 hover:ring-line-strong" aria-label="Open account menu">
                <Avatar :user="page.props.auth.user" size="md" />
            </button>
        </template>
        <div class="flex items-center gap-3 px-2.5 py-2">
            <Avatar :user="page.props.auth.user" size="lg" decorative />
            <div class="min-w-0">
                <p class="truncate text-body font-semibold text-ink">{{ page.props.auth.user.name }}</p>
                <p class="truncate text-small text-ink-3">{{ page.props.auth.user.email }}</p>
            </div>
        </div>
        <DropdownSeparator />
        <DropdownItem :href="route('settings.general')" :icon="Settings">Account settings</DropdownItem>
        <DropdownItem :icon="Keyboard" shortcut="?" @select="emit('shortcuts')">Keyboard shortcuts</DropdownItem>
        <DropdownSeparator />
        <DropdownLabel>Theme</DropdownLabel>
        <DropdownItem v-for="theme in themes" :key="theme.value" :icon="theme.icon" :active="preference === theme.value" @select="setTheme(theme.value)">
            {{ theme.label }}
            <template #end><Check v-if="preference === theme.value" class="size-4 text-accent-text" /></template>
        </DropdownItem>
        <DropdownSeparator />
        <DropdownItem :href="route('docs')" :icon="BookOpen">Help &amp; documentation</DropdownItem>
        <DropdownItem :href="route('home')" :icon="Globe">OrbitOps website</DropdownItem>
        <DropdownSeparator />
        <DropdownItem :icon="LogOut" danger @select="logout">Log out</DropdownItem>
    </Dropdown>
</template>
