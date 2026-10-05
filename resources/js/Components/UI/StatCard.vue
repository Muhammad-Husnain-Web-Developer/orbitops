<script setup>
import { ArrowDownRight, ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';
import Sparkline from '@/Components/Charts/Sparkline.vue';
import { useCountUp } from '@/composables/useCountUp';
import { formatDuration, formatMoney, formatNumber } from '@/lib/format';
import Skeleton from './Skeleton.vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: Number, default: 0 },
    format: { type: String, default: 'number' },
    currency: { type: String, default: 'USD' },
    delta: { type: Number, default: null },
    deltaLabel: { type: String, default: 'vs last month' },
    upIsGood: { type: Boolean, default: true },
    trend: { type: Array, default: null },
    icon: { type: [Object, Function], default: null },
    hint: { type: String, default: null },
    loading: { type: Boolean, default: false },
});

const animated = useCountUp(() => props.value);

const display = computed(() => {
    const value = animated.value;

    switch (props.format) {
        case 'money':
            return formatMoney(Math.round(value), props.currency, { decimals: 0 });
        case 'percent':
            return `${Math.round(value)}%`;
        case 'duration':
            return formatDuration(value, { compact: true });
        default:
            return formatNumber(Math.round(value));
    }
});

// Changes on a tiny base are huge but meaningless; cap what we print.
const deltaText = computed(() => (Math.abs(props.delta) >= 1000 ? '>999%' : `${Math.abs(props.delta).toFixed(props.delta % 1 === 0 ? 0 : 1)}%`));

const good = computed(() => (props.delta ?? 0) === 0 || (props.delta > 0) === props.upIsGood);
</script>

<template>
    <div class="group relative flex flex-col justify-between overflow-hidden rounded-xl border border-line bg-surface p-4 shadow-card transition-colors duration-200 hover:border-line-strong sm:p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-small font-medium text-ink-3">{{ label }}</p>
            <span v-if="icon" class="flex size-7 items-center justify-center rounded-lg bg-subtle text-ink-3 transition-colors group-hover:text-accent-text">
                <component :is="icon" class="size-3.5" aria-hidden="true" />
            </span>
        </div>
        <template v-if="loading">
            <Skeleton class="mt-4 h-7 w-28" />
            <Skeleton class="mt-3 h-3 w-20" />
        </template>
        <template v-else>
            <div class="mt-3 flex items-end justify-between gap-3">
                <p class="text-[1.625rem] leading-none font-semibold tracking-[-0.03em] text-ink sm:text-[1.75rem]">{{ display }}</p>
                <Sparkline v-if="trend?.length" :values="trend" class="mb-0.5 hidden shrink-0 sm:block" />
            </div>
            <p class="mt-2.5 flex items-center gap-1.5 text-caption text-ink-3">
                <span
                    v-if="delta !== null"
                    class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-px font-medium tabular"
                    :class="good ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                >
                    <component :is="delta >= 0 ? ArrowUpRight : ArrowDownRight" class="size-3" aria-hidden="true" />
                    {{ deltaText }}
                    <span class="sr-only">{{ delta >= 0 ? 'increase' : 'decrease' }}</span>
                </span>
                <span>{{ hint ?? (delta !== null ? deltaLabel : 'No earlier period to compare') }}</span>
            </p>
        </template>
    </div>
</template>
