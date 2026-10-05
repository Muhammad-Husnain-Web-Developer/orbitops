<script setup>
import { Link, router } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Modal from '@/Components/UI/Modal.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import SegmentedControl from '@/Components/UI/SegmentedControl.vue';
import { swatch } from '@/lib/colors';
import { formatDate, toDate, toDateInput } from '@/lib/format';

const props = defineProps({
    month: { type: String, required: true },
    range: { type: Object, required: true },
    scope: { type: String, default: 'all' },
    events: { type: Array, required: true },
});

const loading = ref(false);
const scopeModel = computed({ get: () => props.scope, set: (scope) => visit(props.month, scope) });
const dayDetail = ref(null);
const detailOpen = computed({ get: () => Boolean(dayDetail.value), set: (value) => !value && (dayDetail.value = null) });

const today = toDateInput();
const monthDate = computed(() => toDate(`${props.month}-01`));
const title = computed(() => formatDate(monthDate.value, 'month'));
const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const byDate = computed(() => {
    const map = new Map();
    for (const event of props.events) {
        if (!map.has(event.date)) map.set(event.date, []);
        map.get(event.date).push(event);
    }
    return map;
});

const days = computed(() => {
    const list = [];
    const cursor = toDate(props.range.from);
    const end = toDate(props.range.to);

    while (cursor <= end) {
        const key = toDateInput(cursor);
        list.push({ key, day: cursor.getDate(), inMonth: cursor.getMonth() === monthDate.value.getMonth(), events: byDate.value.get(key) ?? [] });
        cursor.setDate(cursor.getDate() + 1);
    }

    return list;
});

const agenda = computed(() => days.value.filter((day) => day.inMonth && day.events.length));

function shift(offset) {
    const date = new Date(monthDate.value);
    date.setMonth(date.getMonth() + offset);
    visit(`${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`);
}

function visit(month, scope = props.scope) {
    router.get(route('calendar'), { month, scope: scope === 'mine' ? 'mine' : undefined }, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}

const kinds = {
    task: { label: 'Task', shape: 'rounded-full' },
    milestone: { label: 'Milestone', shape: 'rotate-45 rounded-[1px]' },
    project: { label: 'Project deadline', shape: 'rounded-[1px]' },
    invoice: { label: 'Invoice due', shape: 'rounded-full ring-2 ring-offset-0' },
};
</script>

<template>
    <PageHeader title="Calendar" description="Deadlines, milestones and invoice due dates in one view.">
        <template #actions>
            <SegmentedControl
                v-model="scopeModel"
                label="Calendar scope"
                :options="[
                    { value: 'all', label: 'Everyone' },
                    { value: 'mine', label: 'My tasks' },
                ]"
            />
        </template>
    </PageHeader>

    <Card :padded="false">
        <div class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-3 sm:px-5">
            <h2 class="text-h3 text-ink" aria-live="polite">{{ title }}</h2>
            <div class="flex items-center gap-1">
                <Button variant="ghost" size="sm" square :icon="ChevronLeft" aria-label="Previous month" @click="shift(-1)" />
                <Button variant="ghost" size="sm" square :icon="ChevronRight" aria-label="Next month" @click="shift(1)" />
            </div>
            <Button variant="secondary" size="sm" @click="visit(today.slice(0, 7))">Today</Button>
            <ul class="ml-auto hidden items-center gap-4 text-caption text-ink-3 md:flex" aria-label="Legend">
                <li v-for="(kind, key) in kinds" :key="key" class="flex items-center gap-1.5"><span class="size-2 bg-ink-3" :class="kind.shape" />{{ kind.label }}</li>
            </ul>
        </div>

        <!-- Month grid (tablet and up) -->
        <div class="hidden md:block" :class="loading ? 'opacity-60 transition-opacity' : 'transition-opacity'">
            <div class="grid grid-cols-7 border-b border-line">
                <div v-for="weekday in weekdays" :key="weekday" class="px-3 py-2 text-caption font-medium text-ink-3">{{ weekday }}</div>
            </div>
            <div class="grid grid-cols-7">
                <div
                    v-for="(day, index) in days"
                    :key="day.key"
                    class="min-h-32 border-b border-line p-1.5"
                    :class="[index % 7 !== 6 ? 'border-r' : '', day.inMonth ? '' : 'bg-subtle/40']"
                >
                    <div class="mb-1 flex items-center justify-between px-1">
                        <span
                            class="flex size-6 items-center justify-center rounded-full text-caption font-medium tabular"
                            :class="day.key === today ? 'bg-accent text-accent-ink' : day.inMonth ? 'text-ink-2' : 'text-ink-3'"
                            :aria-current="day.key === today ? 'date' : undefined"
                            >{{ day.day }}</span
                        >
                    </div>
                    <ul class="space-y-0.5">
                        <li v-for="event in day.events.slice(0, 3)" :key="event.id">
                            <Link :href="event.url" class="group flex items-center gap-1.5 rounded-md px-1.5 py-0.5 text-caption transition-colors hover:bg-hover" :title="`${event.title} — ${event.meta}`">
                                <span class="size-[7px] shrink-0" :class="[kinds[event.type].shape, swatch(event.color)]" />
                                <span class="truncate" :class="event.done ? 'text-ink-3 line-through' : 'text-ink-2 group-hover:text-ink'">{{ event.title }}</span>
                            </Link>
                        </li>
                        <li v-if="day.events.length > 3">
                            <button type="button" class="w-full rounded-md px-1.5 py-0.5 text-left text-caption font-medium text-accent-text hover:bg-hover" @click="dayDetail = day">+{{ day.events.length - 3 }} more</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Agenda (mobile) -->
        <div class="md:hidden">
            <EmptyState v-if="!agenda.length" compact :icon="CalendarDays" title="Nothing scheduled" description="No deadlines this month." />
            <section v-for="day in agenda" :key="day.key" class="border-b border-line px-4 py-3 last:border-b-0">
                <h3 class="mb-2 text-small font-semibold" :class="day.key === today ? 'text-accent-text' : 'text-ink'">{{ formatDate(day.key, 'long') }}</h3>
                <ul class="space-y-1.5">
                    <li v-for="event in day.events" :key="event.id">
                        <Link :href="event.url" class="flex items-center gap-2.5 rounded-lg bg-subtle/60 px-3 py-2">
                            <span class="size-2 shrink-0" :class="[kinds[event.type].shape, swatch(event.color)]" />
                            <span class="min-w-0">
                                <span class="block truncate text-body" :class="event.done ? 'text-ink-3 line-through' : 'text-ink'">{{ event.title }}</span>
                                <span class="block truncate text-caption text-ink-3">{{ event.meta }}</span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </Card>

    <Modal v-model:open="detailOpen" :title="dayDetail ? formatDate(dayDetail.key, 'long') : ''" size="sm">
        <ul v-if="dayDetail" class="-mx-2 space-y-1">
            <li v-for="event in dayDetail.events" :key="event.id">
                <Link :href="event.url" class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-hover">
                    <span class="size-2 shrink-0" :class="[kinds[event.type].shape, swatch(event.color)]" />
                    <span class="min-w-0">
                        <span class="block truncate text-body text-ink">{{ event.title }}</span>
                        <span class="block truncate text-caption text-ink-3">{{ event.meta }}</span>
                    </span>
                </Link>
            </li>
        </ul>
    </Modal>
</template>
