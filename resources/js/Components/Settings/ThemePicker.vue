<script setup>
import { Check, Monitor, Moon, Sun } from '@lucide/vue';
import SettingsSection from '@/Components/Settings/SettingsSection.vue';
import { useTheme } from '@/composables/useTheme';

const { preference, setTheme } = useTheme();

const themes = [
    { value: 'dark', label: 'Dark', icon: Moon, description: 'Easy on the eyes. The default.' },
    { value: 'light', label: 'Light', icon: Sun, description: 'Crisp and bright for daytime work.' },
    { value: 'system', label: 'System', icon: Monitor, description: 'Follows your device setting.' },
];
</script>

<template>
    <SettingsSection title="Theme" description="Saved to your account, so it follows you across devices.">
        <div class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Theme">
            <button
                v-for="theme in themes"
                :key="theme.value"
                type="button"
                role="radio"
                :aria-checked="preference === theme.value"
                class="group relative overflow-hidden rounded-xl border text-left transition-colors focus-visible:outline-2 focus-visible:outline-accent"
                :class="preference === theme.value ? 'border-accent ring-1 ring-accent' : 'border-line hover:border-line-strong'"
                @click="setTheme(theme.value)"
            >
                <!-- Miniature of the app in that theme -->
                <div class="flex h-24 gap-1.5 p-2.5" :class="theme.value === 'light' ? 'bg-[#f6f6f8]' : theme.value === 'dark' ? 'bg-[#0b0b10]' : 'bg-gradient-to-r from-[#f6f6f8] from-50% to-[#0b0b10] to-50%'">
                    <div class="w-1/4 rounded-md" :class="theme.value === 'light' ? 'bg-white' : 'bg-[#15151c]'" />
                    <div class="flex flex-1 flex-col gap-1.5">
                        <div class="h-3 w-2/3 rounded" :class="theme.value === 'light' ? 'bg-[#e6e6eb]' : 'bg-[#22222b]'" />
                        <div class="flex-1 rounded-md" :class="theme.value === 'light' ? 'bg-white' : theme.value === 'dark' ? 'bg-[#15151c]' : 'bg-[#22222b]/40'" />
                        <div class="h-2 w-1/3 rounded bg-accent" />
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 border-t border-line bg-surface px-3 py-2.5">
                    <div>
                        <p class="flex items-center gap-1.5 text-body font-medium text-ink"><component :is="theme.icon" class="size-4" />{{ theme.label }}</p>
                        <p class="text-caption text-ink-3">{{ theme.description }}</p>
                    </div>
                    <span v-if="preference === theme.value" class="flex size-5 shrink-0 items-center justify-center rounded-full bg-accent text-accent-ink"><Check class="size-3" stroke-width="3" /></span>
                </div>
            </button>
        </div>
    </SettingsSection>
</template>
