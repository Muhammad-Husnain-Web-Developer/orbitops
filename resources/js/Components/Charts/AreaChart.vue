<script setup>
import { computed, onMounted, ref, useId } from 'vue';
import { prefersReducedMotion } from '@/composables/useReducedMotion';
import { chartSlots } from '@/lib/colors';
import ChartLegend from './ChartLegend.vue';
import ChartTable from './ChartTable.vue';
import { monotonePath, niceTicks, tooltipPosition, useWidth } from './chart';

const props = defineProps({
    labels: { type: Array, required: true },
    series: { type: Array, required: true },
    format: { type: Function, default: (value) => String(value) },
    formatAxis: { type: Function, default: null },
    height: { type: Number, default: 240 },
    title: { type: String, required: true },
    area: { type: Boolean, default: true },
});

const id = useId();
const { el, width } = useWidth();
const active = ref(null);
const pointerX = ref(0);
const drawn = ref(false);

const margin = { top: 12, right: 12, bottom: 28, left: 52 };

const colored = computed(() => props.series.map((item, index) => ({ ...item, color: item.color ?? chartSlots[index] })));
const innerWidth = computed(() => Math.max(0, width.value - margin.left - margin.right));
const innerHeight = computed(() => props.height - margin.top - margin.bottom);
const maxValue = computed(() => Math.max(0, ...colored.value.flatMap((item) => item.values)));
const ticks = computed(() => niceTicks(maxValue.value));
const top = computed(() => ticks.value.at(-1) || 1);

const x = (index) => margin.left + (props.labels.length <= 1 ? innerWidth.value / 2 : (index / (props.labels.length - 1)) * innerWidth.value);
const y = (value) => margin.top + innerHeight.value - (value / top.value) * innerHeight.value;

const paths = computed(() =>
    colored.value.map((item) => {
        const points = item.values.map((value, index) => [x(index), y(value)]);
        const line = monotonePath(points);
        const baseline = margin.top + innerHeight.value;

        return {
            ...item,
            line,
            area: points.length ? `${line}L${points.at(-1)[0]},${baseline}L${points[0][0]},${baseline}Z` : '',
        };
    }),
);

const xLabels = computed(() => {
    const every = Math.max(1, Math.ceil(props.labels.length / Math.max(2, Math.floor(innerWidth.value / 72))));

    return props.labels.map((label, index) => ({ label, index, show: index % every === 0 || index === props.labels.length - 1 }));
});

const axisFormat = computed(() => props.formatAxis ?? props.format);

function onPointer(event) {
    const rect = el.value.getBoundingClientRect();
    const relative = event.clientX - rect.left - margin.left;
    const step = props.labels.length > 1 ? innerWidth.value / (props.labels.length - 1) : innerWidth.value;
    active.value = Math.min(props.labels.length - 1, Math.max(0, Math.round(relative / step)));
    pointerX.value = x(active.value);
}

function onKey(event) {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End', 'Escape'].includes(event.key)) return;
    event.preventDefault();

    const last = props.labels.length - 1;
    if (event.key === 'Escape') return (active.value = null);
    if (event.key === 'Home') active.value = 0;
    else if (event.key === 'End') active.value = last;
    else active.value = Math.min(last, Math.max(0, (active.value ?? (event.key === 'ArrowLeft' ? last + 1 : -1)) + (event.key === 'ArrowLeft' ? -1 : 1)));

    pointerX.value = x(active.value);
}

onMounted(() => requestAnimationFrame(() => (drawn.value = true)));
const animate = !prefersReducedMotion();
</script>

<template>
    <figure class="w-full">
        <ChartLegend v-if="colored.length > 1" :series="colored" class="mb-3" />
        <div ref="el" class="relative w-full" :style="{ height: `${height}px` }">
            <svg
                v-if="width"
                :width="width"
                :height="height"
                role="img"
                :aria-label="title"
                tabindex="0"
                class="block overflow-visible rounded-md outline-none focus-visible:ring-2 focus-visible:ring-accent/40"
                @pointermove="onPointer"
                @pointerleave="active = null"
                @keydown="onKey"
                @blur="active = null"
            >
                <defs>
                    <linearGradient v-for="item in paths" :id="`${id}-${item.key}`" :key="item.key" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="item.color" stop-opacity="0.16" />
                        <stop offset="100%" :stop-color="item.color" stop-opacity="0" />
                    </linearGradient>
                </defs>

                <g aria-hidden="true">
                    <template v-for="tick in ticks" :key="tick">
                        <line :x1="margin.left" :x2="width - margin.right" :y1="y(tick)" :y2="y(tick)" :stroke="tick === 0 ? 'var(--chart-axis)' : 'var(--chart-grid)'" stroke-width="1" shape-rendering="crispEdges" />
                        <text :x="margin.left - 10" :y="y(tick)" dy="0.32em" text-anchor="end" class="fill-ink-3 text-[11px] tabular">{{ axisFormat(tick) }}</text>
                    </template>
                    <template v-for="tickLabel in xLabels" :key="tickLabel.index">
                        <text v-if="tickLabel.show" :x="x(tickLabel.index)" :y="height - 8" text-anchor="middle" class="fill-ink-3 text-[11px]">{{ tickLabel.label }}</text>
                    </template>
                </g>

                <g aria-hidden="true">
                    <path
                        v-for="item in area ? paths : []"
                        :key="`a-${item.key}`"
                        :d="item.area"
                        :fill="`url(#${id}-${item.key})`"
                        class="transition-opacity duration-700"
                        :style="{ opacity: drawn || !animate ? 1 : 0 }"
                    />
                    <path
                        v-for="item in paths"
                        :key="`l-${item.key}`"
                        :d="item.line"
                        fill="none"
                        :stroke="item.color"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        pathLength="1"
                        :stroke-dasharray="animate ? 1 : undefined"
                        :stroke-dashoffset="animate ? (drawn ? 0 : 1) : undefined"
                        class="transition-[stroke-dashoffset] duration-[1200ms] ease-[var(--ease-out-expo)]"
                    />
                </g>

                <g v-if="active !== null" aria-hidden="true">
                    <line :x1="pointerX" :x2="pointerX" :y1="margin.top" :y2="margin.top + innerHeight" stroke="var(--line-strong)" stroke-width="1" shape-rendering="crispEdges" />
                    <circle v-for="item in paths" :key="`d-${item.key}`" :cx="pointerX" :cy="y(item.values[active] ?? 0)" r="4" :fill="item.color" stroke="var(--surface)" stroke-width="2" />
                </g>
            </svg>

            <div
                v-if="active !== null"
                class="pointer-events-none absolute top-2 z-10 min-w-36 rounded-lg border border-line bg-elevated px-3 py-2 shadow-overlay"
                :style="{ left: `${tooltipPosition(pointerX, width)}px` }"
                aria-hidden="true"
            >
                <p class="mb-1 text-caption text-ink-3">{{ labels[active] }}</p>
                <div v-for="item in colored" :key="item.key" class="flex items-center gap-2 py-0.5">
                    <span class="h-0.5 w-2.5 rounded-full" :style="{ background: item.color }" />
                    <span class="text-small font-semibold text-ink tabular">{{ format(item.values[active] ?? 0) }}</span>
                    <span class="text-caption text-ink-3">{{ item.name }}</span>
                </div>
            </div>
        </div>
        <ChartTable :caption="title" :labels="labels" :series="colored" :format="format" />
    </figure>
</template>
