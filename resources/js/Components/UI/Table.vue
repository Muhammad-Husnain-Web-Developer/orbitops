<script setup>
import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';
import Skeleton from './Skeleton.vue';

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    sort: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    skeletonRows: { type: Number, default: 6 },
    clickable: { type: Boolean, default: false },
    caption: { type: String, default: null },
});

const emit = defineEmits(['sort', 'row-click']);

const hide = { sm: 'hidden sm:table-cell', md: 'hidden md:table-cell', lg: 'hidden lg:table-cell', xl: 'hidden xl:table-cell' };
const align = { right: 'text-right', center: 'text-center', left: 'text-left' };

function ariaSort(column) {
    if (!column.sortable || props.sort?.key !== column.key) return undefined;

    return props.sort.direction === 'asc' ? 'ascending' : 'descending';
}

function onRowClick(row, event) {
    if (!props.clickable || event.target.closest('a, button, input, select, label')) return;
    emit('row-click', row);
}
</script>

<template>
    <div>
        <!-- Desktop / tablet table -->
        <div class="overflow-x-auto" :class="$slots.mobile ? 'hidden md:block' : ''">
            <table class="w-full border-separate border-spacing-0 text-left">
                <caption v-if="caption" class="sr-only">{{ caption }}</caption>
                <thead>
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            :aria-sort="ariaSort(column)"
                            class="sticky top-0 border-b border-line bg-surface px-4 py-2.5 text-caption font-medium whitespace-nowrap text-ink-3 first:pl-5 last:pr-5"
                            :class="[hide[column.hide], align[column.align ?? 'left'], column.width]"
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="group inline-flex items-center gap-1 rounded transition-colors hover:text-ink"
                                :class="sort?.key === column.key ? 'text-ink' : ''"
                                @click="emit('sort', column.key)"
                            >
                                {{ column.label }}
                                <ArrowUp v-if="sort?.key === column.key && sort.direction === 'asc'" class="size-3" aria-hidden="true" />
                                <ArrowDown v-else-if="sort?.key === column.key" class="size-3" aria-hidden="true" />
                                <ChevronsUpDown v-else class="size-3 opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" />
                            </button>
                            <span v-else :class="column.srOnly ? 'sr-only' : ''">{{ column.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-if="loading && !rows.length">
                        <tr v-for="n in skeletonRows" :key="`s${n}`">
                            <td v-for="column in columns" :key="column.key" class="border-b border-line px-4 py-3.5 first:pl-5 last:pr-5" :class="hide[column.hide]">
                                <Skeleton class="h-3.5" :class="n % 2 ? 'w-3/4' : 'w-1/2'" />
                            </td>
                        </tr>
                    </template>
                    <tr
                        v-for="row in rows"
                        v-else
                        :key="row[rowKey]"
                        class="group transition-colors duration-100"
                        :class="[clickable ? 'cursor-pointer hover:bg-hover' : '', loading ? 'opacity-60' : '']"
                        @click="onRowClick(row, $event)"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="border-b border-line px-4 py-3 align-middle text-body text-ink-2 group-last:border-b-0 first:pl-5 last:pr-5"
                            :class="[hide[column.hide], align[column.align ?? 'left'], column.class]"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">{{ row[column.key] ?? '—' }}</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <ul v-if="$slots.mobile" class="divide-y divide-line md:hidden">
            <template v-if="loading && !rows.length">
                <li v-for="n in 4" :key="`m${n}`" class="space-y-2 px-4 py-4">
                    <Skeleton class="h-4 w-2/3" />
                    <Skeleton class="h-3 w-1/3" />
                </li>
            </template>
            <li v-for="row in rows" v-else :key="row[rowKey]" class="px-4 py-3.5" :class="clickable ? 'active:bg-hover' : ''" @click="onRowClick(row, $event)">
                <slot name="mobile" :row="row" />
            </li>
        </ul>

        <div v-if="!loading && !rows.length">
            <slot name="empty" />
        </div>
    </div>
</template>
