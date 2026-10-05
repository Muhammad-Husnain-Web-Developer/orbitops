<script setup>
import { Moon, Sun } from '@lucide/vue';
import { useTheme } from '@/composables/useTheme';

const { isDark, toggle } = useTheme();
</script>

<template>
    <button
        type="button"
        class="inline-flex size-9 items-center justify-center rounded-lg text-ink-3 transition-colors hover:bg-hover hover:text-ink"
        :aria-label="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
        :title="isDark ? 'Light theme' : 'Dark theme'"
        @click="toggle"
    >
        <Transition mode="out-in" enter-active-class="duration-200" enter-from-class="rotate-45 scale-75 opacity-0" leave-active-class="duration-150" leave-to-class="-rotate-45 scale-75 opacity-0">
            <Sun v-if="isDark" class="size-4" />
            <Moon v-else class="size-4" />
        </Transition>
    </button>
</template>
