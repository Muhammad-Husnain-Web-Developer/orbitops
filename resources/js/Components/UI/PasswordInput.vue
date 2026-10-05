<script setup>
import { Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    invalid: { type: Boolean, default: false },
    strength: { type: Boolean, default: false },
});

const visible = ref(false);

/** A lightweight strength estimate for guidance only; the server enforces the real rules. */
const score = computed(() => {
    const value = model.value ?? '';
    if (!value) return 0;

    let points = 0;
    if (value.length >= 8) points++;
    if (value.length >= 12) points++;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) points++;
    if (/\d/.test(value) && /[^A-Za-z0-9]/.test(value)) points++;

    return Math.max(1, points);
});

const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
const tones = ['', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];
</script>

<template>
    <div :class="$attrs.class">
        <div class="relative">
            <input
                v-model="model"
                v-bind="{ ...$attrs, class: undefined }"
                :type="visible ? 'text' : 'password'"
                :aria-invalid="invalid || undefined"
                class="h-9 w-full rounded-lg border border-line bg-surface pr-10 pl-3 text-body text-ink shadow-card transition-[border-color,box-shadow] duration-150 placeholder:text-ink-3 hover:border-line-strong focus:border-accent focus:outline-none focus:ring-3 focus:ring-accent/20 aria-invalid:border-danger aria-invalid:focus:ring-danger/20"
            />
            <button
                type="button"
                class="absolute top-1/2 right-1.5 -translate-y-1/2 rounded-md p-1.5 text-ink-3 transition-colors hover:text-ink"
                :aria-label="visible ? 'Hide password' : 'Show password'"
                :aria-pressed="visible"
                @click="visible = !visible"
            >
                <EyeOff v-if="visible" class="size-4" />
                <Eye v-else class="size-4" />
            </button>
        </div>
        <div v-if="props.strength && model" class="mt-2 flex items-center gap-2" aria-live="polite">
            <div class="flex flex-1 gap-1" aria-hidden="true">
                <span v-for="n in 4" :key="n" class="h-1 flex-1 rounded-full transition-colors duration-300" :class="n <= score ? tones[score] : 'bg-subtle'" />
            </div>
            <span class="w-12 text-right text-caption text-ink-3">{{ labels[score] }}</span>
        </div>
    </div>
</template>
