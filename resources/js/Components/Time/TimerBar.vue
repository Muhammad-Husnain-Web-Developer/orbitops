<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { CircleDollarSign, Play, RotateCcw, Square } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import { useProjectTasks } from '@/composables/useProjectTasks';
import { swatch } from '@/lib/colors';
import { formatDuration, toDate } from '@/lib/format';

const props = defineProps({
    projects: { type: Array, required: true },
    recent: { type: Array, default: () => [] },
});

const page = usePage();
const timer = computed(() => page.props.timer);

const form = useForm({ description: '', project_id: '', task_id: '', billable: true });
const { tasks, loading: tasksLoading } = useProjectTasks(() => form.project_id);

const projectOptions = computed(() => props.projects.map((project) => ({ value: project.id, label: project.name })));
const taskOptions = computed(() => tasks.value.filter((task) => !task.done).map((task) => ({ value: task.id, label: task.title })));

const now = ref(Date.now());
const stopping = ref(false);
let interval;
onMounted(() => (interval = setInterval(() => (now.value = Date.now()), 1000)));
onBeforeUnmount(() => clearInterval(interval));

const elapsed = computed(() => (timer.value ? Math.max(0, Math.floor((now.value - toDate(timer.value.started_at).getTime()) / 1000)) : 0));

function start() {
    form.post(route('timer.start'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function restart(item) {
    router.post(route('timer.start'), { project_id: item.project_id, task_id: item.task_id, description: item.description }, { preserveScroll: true });
}

function stop() {
    router.post(route('timer.stop'), {}, {
        preserveScroll: true,
        onStart: () => (stopping.value = true),
        onFinish: () => (stopping.value = false),
    });
}
</script>

<template>
    <section class="overflow-hidden rounded-xl border bg-surface shadow-card transition-colors" :class="timer ? 'border-danger/30' : 'border-line'" aria-label="Timer">
        <!-- Running -->
        <div v-if="timer" class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:gap-6 sm:px-5">
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <span class="relative flex size-10 shrink-0 items-center justify-center rounded-full bg-danger/10" aria-hidden="true">
                    <span class="absolute inset-0 animate-ping rounded-full bg-danger/10 motion-reduce:hidden" />
                    <span class="size-2.5 rounded-full bg-danger" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-body font-medium text-ink">{{ timer.description || timer.task?.title || 'Untitled work' }}</p>
                    <p class="flex items-center gap-1.5 truncate text-small text-ink-3">
                        <span class="size-2 shrink-0 rounded-full" :class="swatch(timer.project?.color)" />
                        <span class="truncate">{{ timer.project?.name }}<template v-if="timer.task && timer.description"> · {{ timer.task.title }}</template></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-between gap-4 sm:justify-end">
                <p class="font-mono text-[1.75rem] leading-none font-medium tracking-tight text-ink tabular" role="timer" aria-live="off">{{ formatDuration(elapsed, { clock: true }) }}</p>
                <Button variant="danger" :icon="Square" :loading="stopping" @click="stop">Stop</Button>
            </div>
        </div>

        <!-- Idle -->
        <form v-else class="p-3 sm:p-4" @submit.prevent="start">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <label for="timer-description" class="sr-only">What are you working on?</label>
                <input
                    id="timer-description"
                    v-model="form.description"
                    maxlength="190"
                    placeholder="What are you working on?"
                    class="h-9 min-w-0 flex-1 rounded-lg border border-transparent bg-transparent px-3 text-body text-ink placeholder:text-ink-3 hover:border-line focus:border-accent focus:outline-none"
                />
                <div class="grid grid-cols-2 gap-2 lg:flex lg:items-center">
                    <Select v-model="form.project_id" :options="projectOptions" placeholder="Project" aria-label="Project" :invalid="Boolean(form.errors.project_id)" class="lg:w-44" @update:model-value="form.task_id = ''" />
                    <Select v-model="form.task_id" :options="taskOptions" :placeholder="tasksLoading ? 'Loading…' : 'Task (optional)'" aria-label="Task" :disabled="!form.project_id" class="lg:w-48" />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg border transition-colors"
                        :class="form.billable ? 'border-success/30 bg-success/10 text-success' : 'border-line text-ink-3 hover:text-ink'"
                        :aria-pressed="form.billable"
                        :title="form.billable ? 'Billable' : 'Non-billable'"
                        @click="form.billable = !form.billable"
                    >
                        <CircleDollarSign class="size-4" />
                        <span class="sr-only">Billable</span>
                    </button>
                    <Button type="submit" :icon="Play" :loading="form.processing" class="flex-1 lg:flex-none">Start timer</Button>
                </div>
            </div>
            <p v-if="form.errors.project_id" class="mt-2 px-1 text-small text-danger" role="alert">Choose a project to start the timer.</p>

            <div v-if="recent.length" class="mt-3 flex flex-wrap items-center gap-2 border-t border-line px-1 pt-3">
                <span class="text-caption text-ink-3">Continue</span>
                <button
                    v-for="item in recent"
                    :key="`${item.project_id}-${item.task_id}-${item.description}`"
                    type="button"
                    class="group inline-flex max-w-full items-center gap-1.5 rounded-full border border-line bg-canvas/40 py-1 pr-2.5 pl-2 text-small text-ink-2 transition-colors hover:border-line-strong hover:text-ink"
                    @click="restart(item)"
                >
                    <RotateCcw class="size-3 text-ink-3 group-hover:text-accent-text" />
                    <span class="size-1.5 shrink-0 rounded-full" :class="swatch(item.project.color)" />
                    <span class="max-w-56 truncate">{{ item.description || item.task || item.project.name }}</span>
                </button>
            </div>
        </form>
    </section>
</template>
