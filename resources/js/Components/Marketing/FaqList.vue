<script setup>
import { Plus } from '@lucide/vue';
import { ref, useId } from 'vue';

defineProps({
    items: { type: Array, required: true },
});

const open = ref(0);
const id = useId();
</script>

<template>
    <div class="divide-y divide-line border-y border-line">
        <div v-for="(item, index) in items" :key="item.q" data-reveal>
            <h3>
                <button
                    :id="`${id}-q-${index}`"
                    type="button"
                    class="flex w-full items-center justify-between gap-6 py-5 text-left text-body font-medium text-ink transition-colors hover:text-accent-text sm:text-[0.9375rem]"
                    :aria-expanded="open === index"
                    :aria-controls="`${id}-a-${index}`"
                    @click="open = open === index ? null : index"
                >
                    {{ item.q }}
                    <Plus class="size-4 shrink-0 text-ink-3 transition-transform duration-300" :class="open === index ? 'rotate-45' : ''" aria-hidden="true" />
                </button>
            </h3>
            <div :id="`${id}-a-${index}`" role="region" :aria-labelledby="`${id}-q-${index}`" :inert="open !== index || undefined" class="grid transition-[grid-template-rows] duration-300 ease-[var(--ease-out-expo)]" :class="open === index ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                <div class="overflow-hidden">
                    <p class="max-w-2xl pb-5 text-body text-ink-3">{{ item.a }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
