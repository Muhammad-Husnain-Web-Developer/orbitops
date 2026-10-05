<script setup>
import { Check } from '@lucide/vue';
import { useId } from 'vue';
import { colorKeys, swatch } from '@/lib/colors';

const model = defineModel({ type: String, default: 'violet' });

defineProps({
    label: { type: String, default: 'Colour' },
});

const name = `color-${useId()}`;
</script>

<template>
    <fieldset>
        <legend class="mb-2 text-label text-ink">{{ label }}</legend>
        <div class="flex flex-wrap gap-2">
            <label v-for="key in colorKeys" :key="key" class="cursor-pointer">
                <input v-model="model" type="radio" :name="name" :value="key" class="peer sr-only" :aria-label="key" />
                <span class="flex size-7 items-center justify-center rounded-full ring-offset-2 ring-offset-elevated transition peer-checked:ring-2 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-accent" :class="swatch(key)">
                    <Check v-if="model === key" class="size-3.5 text-white" aria-hidden="true" />
                </span>
            </label>
        </div>
    </fieldset>
</template>
