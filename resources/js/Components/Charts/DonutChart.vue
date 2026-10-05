<script setup>
import { computed, onMounted, ref } from 'vue';
import { prefersReducedMotion } from '@/composables/useReducedMotion';
import { chartSlots } from '@/lib/colors';

const props = defineProps({
    segments: { type: Array, required: true },
    format: { type: Function, default: (value) => String(value) },
    title: { type: String, required: true },
    centerLabel: { type: String, default: 'Total' },
    size: { type: Number, default: 168 },
});

const active = ref(null);
const drawn = ref(false);
const animate = !prefersReducedMotion();

const STROKE = 18;
const radius = computed(() => (props.size - STROKE) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);

/** At most six slices; the tail folds into "Other" so no hue is ever generated. */
const normalized = computed(() => {
    const sorted = [...props.segments].filter((segment) => segment.value > 0).sort((a, b) => b.value - a.value);
    const head = sorted.slice(0, sorted.length > 6 ? 5 : 6);
    const tail = sorted.slice(head.length);

    if (tail.length) {
        head.push({ key: 'other', label: 'Other', value: tail.reduce((sum, item) => sum + item.value, 0) });
    }

    return head.map((segment, index) => ({ ...segment, color: segment.color ?? chartSlots[index] }));
});

const total = computed(() => normalized.value.reduce((sum, segment) => sum + segment.value, 0));

const arcs = computed(() => {
    let offset = 0;
    const gap = normalized.value.length > 1 ? 2 : 0;

    return normalized.value.map((segment) => {
        const length = total.value ? (segment.value / total.value) * circumference.value : 0;
        const arc = { ...segment, length: Math.max(0, length - gap), offset, percent: total.value ? (segment.value / total.value) * 100 : 0 };
        offset += length;

        return arc;
    });
});

const focused = computed(() => (active.value === null ? null : arcs.value[active.value]));

onMounted(() => requestAnimationFrame(() => (drawn.value = true)));
</script>

<template>
    <!-- Layout follows the chart's container, not the viewport, so it works in narrow cards. -->
    <figure class="@container">
        <div class="flex flex-col items-center gap-5 @sm:flex-row @sm:gap-6">
        <div class="relative shrink-0" :style="{ width: `${size}px`, height: `${size}px` }">
            <svg :width="size" :height="size" class="-rotate-90" role="img" :aria-label="title">
                <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" stroke="var(--subtle)" :stroke-width="STROKE" />
                <circle
                    v-for="(arc, index) in arcs"
                    :key="arc.key"
                    :cx="size / 2"
                    :cy="size / 2"
                    :r="radius"
                    fill="none"
                    :stroke="arc.color"
                    :stroke-width="active === index ? STROKE + 4 : STROKE"
                    :stroke-dasharray="`${animate && !drawn ? 0 : arc.length} ${circumference}`"
                    :stroke-dashoffset="-arc.offset"
                    class="cursor-pointer transition-[stroke-dasharray,stroke-width,opacity] duration-700 ease-[var(--ease-out-expo)]"
                    :opacity="active === null || active === index ? 1 : 0.4"
                    @pointerenter="active = index"
                    @pointerleave="active = null"
                />
            </svg>
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-caption text-ink-3">{{ focused ? focused.label : centerLabel }}</span>
                <span class="text-h3 text-ink tabular">{{ format(focused ? focused.value : total) }}</span>
                <span v-if="focused" class="text-caption text-ink-3 tabular">{{ focused.percent.toFixed(0) }}%</span>
            </div>
        </div>
        <ul class="w-full min-w-0 space-y-1">
            <li
                v-for="(arc, index) in arcs"
                :key="arc.key"
                class="flex items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors"
                :class="active === index ? 'bg-hover' : ''"
                tabindex="0"
                @pointerenter="active = index"
                @pointerleave="active = null"
                @focus="active = index"
                @blur="active = null"
            >
                <span class="size-2.5 shrink-0 rounded-[3px]" :style="{ background: arc.color }" aria-hidden="true" />
                <span class="min-w-0 flex-1 truncate text-small text-ink-2">{{ arc.label }}</span>
                <span class="text-small font-medium text-ink tabular">{{ format(arc.value) }}</span>
                <span class="w-10 text-right text-caption text-ink-3 tabular">{{ arc.percent.toFixed(0) }}%</span>
            </li>
        </ul>
        </div>
    </figure>
</template>
