<script setup>
import { router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { nextTick, reactive, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { useEnums } from '@/composables/useEnums';
import TaskCard from './TaskCard.vue';

const props = defineProps({
    tasks: { type: Array, required: true },
    showProject: { type: Boolean, default: true },
    readonly: { type: Boolean, default: false },
    reloadOnly: { type: Array, default: () => ['tasks'] },
});

const emit = defineEmits(['open', 'create']);

const { options } = useEnums();
const statuses = options('taskStatus');
const columns = reactive(Object.fromEntries(statuses.map((status) => [status.value, []])));
const dragging = ref(false);

const dots = { neutral: 'bg-ink-3', info: 'bg-info', accent: 'bg-accent', warning: 'bg-warning', success: 'bg-success' };

function rebuild() {
    for (const status of statuses) {
        columns[status.value] = props.tasks.filter((task) => task.status === status.value);
    }
}

watch(() => props.tasks, () => !dragging.value && rebuild(), { immediate: true, deep: true });

async function onEnd(event) {
    dragging.value = false;
    await nextTick();

    const id = Number(event.item.dataset.id);
    const status = event.to.dataset.status;

    if (!id || !status || (event.from === event.to && event.oldIndex === event.newIndex)) return;

    const list = columns[status];
    const index = list.findIndex((task) => task.id === id);
    if (index === -1) return;

    list[index] = { ...list[index], status };

    router.patch(
        route('tasks.move', id),
        { status, after_id: index > 0 ? list[index - 1].id : null },
        { preserveScroll: true, preserveState: true, only: [...props.reloadOnly, 'activeTask', 'counts'], onError: rebuild },
    );
}
</script>

<template>
    <div class="-mx-4 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" role="region" aria-label="Task board">
        <div class="flex min-w-max snap-x snap-mandatory gap-3 sm:snap-none">
            <section v-for="status in statuses" :key="status.value" class="flex w-[17.5rem] shrink-0 snap-start flex-col rounded-2xl border border-line bg-subtle/40 sm:w-[18.5rem]" :aria-label="`${status.label} column`">
                <header class="flex items-center gap-2 px-3 pt-3 pb-2">
                    <span class="size-2 rounded-full" :class="dots[status.tone]" aria-hidden="true" />
                    <h3 class="text-body font-semibold text-ink">{{ status.label }}</h3>
                    <span class="rounded-full bg-surface px-1.5 text-caption text-ink-3 tabular">{{ columns[status.value].length }}</span>
                    <button v-if="!readonly" type="button" class="ml-auto rounded-md p-1 text-ink-3 transition-colors hover:bg-hover hover:text-ink" :aria-label="`Add task to ${status.label}`" @click="emit('create', status.value)">
                        <Plus class="size-4" />
                    </button>
                </header>
                <VueDraggable
                    v-model="columns[status.value]"
                    :data-status="status.value"
                    group="tasks"
                    :animation="180"
                    :disabled="readonly"
                    ghost-class="kanban-ghost"
                    chosen-class="kanban-chosen"
                    drag-class="kanban-drag"
                    class="flex min-h-24 flex-1 flex-col gap-2 px-2 pb-2"
                    @start="dragging = true"
                    @end="onEnd"
                >
                    <TaskCard v-for="task in columns[status.value]" :key="task.id" :task="task" :show-project="showProject" @open="emit('open', $event)" />
                </VueDraggable>
                <p v-if="!columns[status.value].length" class="pointer-events-none -mt-24 mb-8 px-4 text-center text-caption text-ink-3">Drop tasks here</p>
            </section>
        </div>
    </div>
</template>

<style>
.kanban-ghost {
    opacity: 0.35;
    border-style: dashed;
}

.kanban-drag {
    transform: rotate(2deg);
    box-shadow: 0 20px 40px -12px rgb(0 0 0 / 0.35);
}
</style>
