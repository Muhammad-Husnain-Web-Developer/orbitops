<script setup>
import { Link } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, useId, watch } from 'vue';

const model = defineModel({ type: String, default: null });

const props = defineProps({
    tabs: { type: Array, required: true },
    label: { type: String, default: 'Sections' },
});

const id = useId();
const list = ref(null);
const indicator = ref({ left: 0, width: 0, ready: false });

function measure() {
    const active = list.value?.querySelector('[aria-selected="true"]');
    if (!active) return;

    indicator.value = { left: active.offsetLeft, width: active.offsetWidth, ready: true };
    active.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}

function select(tab) {
    if (!tab.href) model.value = tab.key;
}

function onKeydown(event) {
    const keys = props.tabs.map((tab) => tab.key);
    const index = keys.indexOf(model.value);
    let next = null;

    if (event.key === 'ArrowRight') next = (index + 1) % keys.length;
    if (event.key === 'ArrowLeft') next = (index - 1 + keys.length) % keys.length;
    if (event.key === 'Home') next = 0;
    if (event.key === 'End') next = keys.length - 1;
    if (next === null) return;

    event.preventDefault();
    const tab = props.tabs[next];
    list.value?.querySelectorAll('[role="tab"]')[next]?.focus();

    if (!tab.href) model.value = tab.key;
}

onMounted(() => nextTick(measure));
watch(model, () => nextTick(measure));
watch(() => props.tabs.map((tab) => tab.count), () => nextTick(measure));
</script>

<template>
    <div class="relative border-b border-line">
        <div ref="list" role="tablist" :aria-label="label" class="no-scrollbar -mb-px flex gap-1 overflow-x-auto" @keydown="onKeydown">
            <component
                :is="tab.href ? Link : 'button'"
                v-for="tab in tabs"
                :id="`${id}-${tab.key}`"
                :key="tab.key"
                :href="tab.href"
                :type="tab.href ? undefined : 'button'"
                preserve-scroll
                role="tab"
                :aria-selected="model === tab.key"
                :tabindex="model === tab.key ? 0 : -1"
                class="relative inline-flex h-10 shrink-0 items-center gap-2 rounded-t-md px-3 text-body font-medium whitespace-nowrap transition-colors duration-150"
                :class="model === tab.key ? 'text-ink' : 'text-ink-3 hover:text-ink'"
                @click="select(tab)"
            >
                <component :is="tab.icon" v-if="tab.icon" class="size-4" aria-hidden="true" />
                {{ tab.label }}
                <span v-if="tab.count !== undefined && tab.count !== null" class="rounded-full bg-subtle px-1.5 py-px text-caption text-ink-3 tabular">{{ tab.count }}</span>
            </component>
        </div>
        <span
            class="pointer-events-none absolute bottom-0 h-0.5 rounded-full bg-accent transition-[left,width] duration-300 ease-[var(--ease-out-expo)]"
            :class="indicator.ready ? 'opacity-100' : 'opacity-0'"
            :style="{ left: `${indicator.left}px`, width: `${indicator.width}px` }"
            aria-hidden="true"
        />
    </div>
</template>

<style scoped>
.no-scrollbar {
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
