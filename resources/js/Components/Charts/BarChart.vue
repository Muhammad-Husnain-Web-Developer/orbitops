<script setup>
import { computed, onMounted, ref } from 'vue';
import { prefersReducedMotion } from '@/composables/useReducedMotion';
import { chartSlots } from '@/lib/colors';
import ChartLegend from './ChartLegend.vue';
import ChartTable from './ChartTable.vue';
import { niceTicks, tooltipPosition, useWidth } from './chart';

const props = defineProps({
    labels: { type: Array, required: true },
    series: { type: Array, required: true },
    format: { type: Function, default: (value) => String(value) },
    formatAxis: { type: Function, default: null },
    height: { type: Number, default: 240 },
    title: { type: String, required: true },
    stacked: { type: Boolean, default: false },
    highlightLast: { type: Boolean, default: false },
});

const { el, width } = useWidth();
const active = ref(null);
const grown = ref(false);
const animate = !prefersReducedMotion();

const margin = { top: 12, right: 8, bottom: 28, left: 52 };
const GAP = 2;
const MAX_BAR = 24;
const RADIUS = 4;

const colored = computed(() => props.series.map((item, index) => ({ ...item, color: item.color ?? chartSlots[index] })));
const innerWidth = computed(() => Math.max(0, width.value - margin.left - margin.right));
const innerHeight = computed(() => props.height - margin.top - margin.bottom);
const band = computed(() => innerWidth.value / Math.max(1, props.labels.length));

const maxValue = computed(() => {
    if (props.stacked) {
        return Math.max(0, ...props.labels.map((_, index) => colored.value.reduce((sum, item) => sum + (item.values[index] ?? 0), 0)));
    }

    return Math.max(0, ...colored.value.flatMap((item) => item.values));
});

const ticks = computed(() => niceTicks(maxValue.value));
const top = computed(() => ticks.value.at(-1) || 1);
const y = (value) => margin.top + innerHeight.value - (value / top.value) * innerHeight.value;
const baseline = computed(() => margin.top + innerHeight.value);

/** Rounded data-end (4px) and a square base, per the chart mark spec. */
function barPath(x, yTop, w, h, roundTop) {
    if (h <= 0) return '';
    const r = roundTop ? Math.min(RADIUS, w / 2, h) : 0;

    return `M${x},${yTop + h}V${yTop + r}Q${x},${yTop} ${x + r},${yTop}H${x + w - r}Q${x + w},${yTop} ${x + w},${yTop + r}V${yTop + h}Z`;
}

const bars = computed(() => {
    const groups = [];
    const count = props.stacked ? 1 : colored.value.length;
    const barWidth = Math.max(2, Math.min(MAX_BAR, (band.value * 0.62 - GAP * (count - 1)) / count));
    const groupWidth = barWidth * count + GAP * (count - 1);

    props.labels.forEach((label, index) => {
        const start = margin.left + band.value * index + (band.value - groupWidth) / 2;
        let stackTop = baseline.value;

        colored.value.forEach((item, seriesIndex) => {
            const value = item.values[index] ?? 0;
            const h = (value / top.value) * innerHeight.value;
            const isTop = !props.stacked || seriesIndex === colored.value.length - 1 || colored.value.slice(seriesIndex + 1).every((next) => !(next.values[index] > 0));

            let x = start + (barWidth + GAP) * seriesIndex;
            let yTop = baseline.value - h;

            if (props.stacked) {
                x = start;
                yTop = stackTop - h;
                stackTop = yTop - (h > 0 ? GAP : 0);
            }

            groups.push({ key: `${index}-${item.key}`, index, color: item.color, d: barPath(x, yTop, barWidth, h, isTop) });
        });
    });

    return groups;
});

const xLabels = computed(() => {
    const every = Math.max(1, Math.ceil(props.labels.length / Math.max(2, Math.floor(innerWidth.value / 56))));

    return props.labels.map((label, index) => ({ label, index, show: index % every === 0 }));
});

const axisFormat = computed(() => props.formatAxis ?? props.format);
const centerOf = (index) => margin.left + band.value * index + band.value / 2;

function onKey(event) {
    if (!['ArrowLeft', 'ArrowRight', 'Escape'].includes(event.key)) return;
    event.preventDefault();
    const last = props.labels.length - 1;
    if (event.key === 'Escape') return (active.value = null);
    active.value = Math.min(last, Math.max(0, (active.value ?? (event.key === 'ArrowLeft' ? last + 1 : -1)) + (event.key === 'ArrowLeft' ? -1 : 1)));
}

onMounted(() => requestAnimationFrame(() => (grown.value = true)));
</script>

<template>
    <figure class="w-full">
        <ChartLegend v-if="colored.length > 1" :series="colored" shape="rect" class="mb-3" />
        <div ref="el" class="relative w-full" :style="{ height: `${height}px` }">
            <svg
                v-if="width"
                :width="width"
                :height="height"
                role="img"
                :aria-label="title"
                tabindex="0"
                class="block overflow-visible rounded-md outline-none focus-visible:ring-2 focus-visible:ring-accent/40"
                @pointerleave="active = null"
                @keydown="onKey"
                @blur="active = null"
            >
                <g aria-hidden="true">
                    <template v-for="tick in ticks" :key="tick">
                        <line :x1="margin.left" :x2="width - margin.right" :y1="y(tick)" :y2="y(tick)" :stroke="tick === 0 ? 'var(--chart-axis)' : 'var(--chart-grid)'" stroke-width="1" shape-rendering="crispEdges" />
                        <text :x="margin.left - 10" :y="y(tick)" dy="0.32em" text-anchor="end" class="fill-ink-3 text-[11px] tabular">{{ axisFormat(tick) }}</text>
                    </template>
                    <template v-for="tickLabel in xLabels" :key="tickLabel.index">
                        <text v-if="tickLabel.show" :x="centerOf(tickLabel.index)" :y="height - 8" text-anchor="middle" class="fill-ink-3 text-[11px]">{{ tickLabel.label }}</text>
                    </template>
                </g>

                <rect
                    v-if="active !== null"
                    :x="margin.left + band * active + 2"
                    :y="margin.top"
                    :width="Math.max(0, band - 4)"
                    :height="innerHeight"
                    rx="6"
                    fill="var(--hover)"
                    aria-hidden="true"
                />

                <g
                    aria-hidden="true"
                    class="transition-transform duration-700 ease-[var(--ease-out-expo)]"
                    :style="{ transform: animate && !grown ? 'scaleY(0)' : 'scaleY(1)', transformOrigin: `0 ${baseline}px` }"
                >
                    <path
                        v-for="bar in bars"
                        :key="bar.key"
                        :d="bar.d"
                        :fill="bar.color"
                        class="transition-opacity duration-150"
                        :opacity="active === null || active === bar.index ? (highlightLast && bar.index !== labels.length - 1 && active === null ? 0.55 : 1) : 0.35"
                    />
                </g>

                <rect
                    v-for="(label, index) in labels"
                    :key="`hit-${index}`"
                    :x="margin.left + band * index"
                    :y="margin.top"
                    :width="band"
                    :height="innerHeight"
                    fill="transparent"
                    @pointerenter="active = index"
                />
            </svg>

            <div
                v-if="active !== null"
                class="pointer-events-none absolute top-2 z-10 min-w-36 rounded-lg border border-line bg-elevated px-3 py-2 shadow-overlay"
                :style="{ left: `${tooltipPosition(centerOf(active), width)}px` }"
                aria-hidden="true"
            >
                <p class="mb-1 text-caption text-ink-3">{{ labels[active] }}</p>
                <div v-for="item in colored" :key="item.key" class="flex items-center gap-2 py-0.5">
                    <span class="size-2 rounded-[2px]" :style="{ background: item.color }" />
                    <span class="text-small font-semibold text-ink tabular">{{ format(item.values[active] ?? 0) }}</span>
                    <span class="text-caption text-ink-3">{{ item.name }}</span>
                </div>
            </div>
        </div>
        <ChartTable :caption="title" :labels="labels" :series="colored" :format="format" />
    </figure>
</template>
