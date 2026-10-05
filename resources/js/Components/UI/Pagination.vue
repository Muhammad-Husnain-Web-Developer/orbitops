<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';

const props = defineProps({
    meta: { type: Object, required: true },
    links: { type: Object, default: null },
    only: { type: Array, default: () => [] },
});

const pages = computed(() => (props.meta.links ?? []).slice(1, -1));
const prev = computed(() => props.links?.prev ?? props.meta.links?.[0]?.url ?? null);
const next = computed(() => props.links?.next ?? props.meta.links?.at(-1)?.url ?? null);
const options = computed(() => ({ preserveScroll: true, preserveState: true, only: props.only.length ? props.only : undefined }));
</script>

<template>
    <nav v-if="meta.last_page > 1 || meta.total > 0" class="flex items-center justify-between gap-4 border-t border-line px-5 py-3" aria-label="Pagination">
        <p class="text-small text-ink-3">
            <template v-if="meta.total">
                Showing <span class="font-medium text-ink-2 tabular">{{ formatNumber(meta.from) }}–{{ formatNumber(meta.to) }}</span> of
                <span class="font-medium text-ink-2 tabular">{{ formatNumber(meta.total) }}</span>
            </template>
        </p>
        <div v-if="meta.last_page > 1" class="flex items-center gap-1">
            <component
                :is="prev ? Link : 'span'"
                :href="prev ?? undefined"
                v-bind="prev ? options : {}"
                class="inline-flex size-8 items-center justify-center rounded-md border border-line text-ink-2 transition-colors"
                :class="prev ? 'hover:bg-hover hover:text-ink' : 'pointer-events-none opacity-40'"
                aria-label="Previous page"
                rel="prev"
            >
                <ChevronLeft class="size-4" />
            </component>
            <template v-for="(page, index) in pages" :key="index">
                <span v-if="!page.url" class="hidden px-1.5 text-small text-ink-3 sm:inline">…</span>
                <Link
                    v-else
                    :href="page.url"
                    v-bind="options"
                    class="hidden h-8 min-w-8 items-center justify-center rounded-md px-2 text-small font-medium transition-colors sm:inline-flex tabular"
                    :class="page.active ? 'bg-accent/10 text-accent-text' : 'text-ink-2 hover:bg-hover hover:text-ink'"
                    :aria-current="page.active ? 'page' : undefined"
                    >{{ page.label }}</Link
                >
            </template>
            <span class="px-2 text-small text-ink-3 sm:hidden tabular">{{ meta.current_page }} / {{ meta.last_page }}</span>
            <component
                :is="next ? Link : 'span'"
                :href="next ?? undefined"
                v-bind="next ? options : {}"
                class="inline-flex size-8 items-center justify-center rounded-md border border-line text-ink-2 transition-colors"
                :class="next ? 'hover:bg-hover hover:text-ink' : 'pointer-events-none opacity-40'"
                aria-label="Next page"
                rel="next"
            >
                <ChevronRight class="size-4" />
            </component>
        </div>
    </nav>
</template>
