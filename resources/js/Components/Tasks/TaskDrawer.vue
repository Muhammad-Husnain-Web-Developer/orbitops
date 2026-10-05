<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { Check, Clock, Eye, Link2, Play, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CommentThread from '@/Components/Comments/CommentThread.vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import Dropzone from '@/Components/Files/Dropzone.vue';
import FileList from '@/Components/Files/FileList.vue';
import Button from '@/Components/UI/Button.vue';
import Drawer from '@/Components/UI/Drawer.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import Switch from '@/Components/UI/Switch.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import { confirm } from '@/composables/useConfirm';
import { useEnums } from '@/composables/useEnums';
import { usePermissions } from '@/composables/usePermissions';
import { toast } from '@/composables/useToast';
import { swatch } from '@/lib/colors';
import { formatDate, formatDuration } from '@/lib/format';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    task: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    members: { type: Array, default: () => [] },
    reloadOnly: { type: Array, default: () => ['tasks'] },
});

const page = usePage();
const { options } = useEnums();
const { can } = usePermissions();

const title = ref('');
const description = ref('');
const saved = ref(false);
const editable = computed(() => can('tasks.manage'));
const only = computed(() => ['activeTask', ...props.reloadOnly, 'counts']);

watch(
    () => props.task,
    (task) => {
        title.value = task?.title ?? '';
        description.value = task?.description ?? '';
    },
    { immediate: true },
);

let savedTimer;
function save(field, value) {
    if (!props.task || !editable.value) return;

    router.patch(route('tasks.update', props.task.id), { [field]: value === '' ? null : value }, {
        preserveScroll: true,
        preserveState: true,
        only: only.value,
        onSuccess: () => {
            saved.value = true;
            clearTimeout(savedTimer);
            savedTimer = setTimeout(() => (saved.value = false), 1600);
        },
        onError: (errors) => toast.error(Object.values(errors)[0] ?? 'Could not save the task'),
    });
}

function saveTitle() {
    if (title.value.trim() && title.value !== props.task.title) save('title', title.value.trim());
    else title.value = props.task.title;
}

function saveDescription() {
    if (description.value !== (props.task.description ?? '')) save('description', description.value);
}

function startTimer() {
    router.post(route('timer.start'), { project_id: props.task.project_id, task_id: props.task.id, description: props.task.title }, { preserveScroll: true, preserveState: true });
}

async function copyLink() {
    await navigator.clipboard?.writeText(route('tasks.index', { task: props.task.id }));
    toast.success('Link copied');
}

async function remove() {
    if (await confirm({ title: 'Delete this task?', description: `“${props.task.title}” will be removed from the board.`, confirmLabel: 'Delete task' })) {
        router.delete(route('tasks.destroy', props.task.id), { preserveScroll: true, only: props.reloadOnly, onSuccess: () => (open.value = false) });
    }
}

const memberOptions = computed(() => props.members.map((member) => ({ value: member.id, label: member.name })));
const milestoneOptions = computed(() => (props.task?.milestones ?? []).map((milestone) => ({ value: milestone.id, label: milestone.name })));
const estimateHours = computed(() => (props.task?.estimate_minutes ? props.task.estimate_minutes / 60 : ''));
</script>

<template>
    <Drawer v-model:open="open" width="max-w-2xl">
        <template #header>
            <div v-if="task && !loading" class="flex min-w-0 items-center gap-2 text-small text-ink-3">
                <span class="size-2 rounded-full" :class="swatch(task.project?.color)" />
                <span class="truncate">{{ task.project?.name }}</span>
                <span>/</span>
                <span class="font-mono text-caption">{{ task.key }}</span>
                <Transition enter-active-class="duration-200" enter-from-class="opacity-0" leave-active-class="duration-500" leave-to-class="opacity-0">
                    <span v-if="saved" class="ml-2 inline-flex items-center gap-1 text-caption text-success"><Check class="size-3" />Saved</span>
                </Transition>
            </div>
            <Skeleton v-else class="h-4 w-48" />
        </template>
        <template #actions>
            <template v-if="task && !loading">
                <Button variant="ghost" size="sm" square :icon="Link2" aria-label="Copy link to task" @click="copyLink" />
                <Button v-if="can('tasks.delete')" variant="danger-ghost" size="sm" square :icon="Trash2" aria-label="Delete task" @click="remove" />
            </template>
        </template>

        <div v-if="loading || !task" class="space-y-6 p-6" aria-busy="true" aria-label="Loading task">
            <Skeleton class="h-7 w-3/4" />
            <div class="grid grid-cols-2 gap-4"><Skeleton v-for="n in 6" :key="n" class="h-9" /></div>
            <Skeleton class="h-24 w-full" />
            <Skeleton class="h-16 w-full" />
        </div>

        <div v-else class="space-y-8 p-5 sm:p-6">
            <div>
                <label for="task-title" class="sr-only">Task title</label>
                <input
                    id="task-title"
                    v-model="title"
                    :readonly="!editable"
                    class="-mx-2 w-[calc(100%+1rem)] rounded-lg border border-transparent bg-transparent px-2 py-1 text-h2 text-ink transition-colors hover:border-line focus:border-accent focus:outline-none"
                    @blur="saveTitle"
                    @keydown.enter.prevent="$event.target.blur()"
                />
                <p class="mt-1 text-small text-ink-3">Created by {{ task.creator?.name ?? 'someone' }} · {{ formatDate(task.created_at) }}</p>
            </div>

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="mb-1.5 text-label text-ink-3">Status</dt>
                    <dd><Select :model-value="task.status" :options="options('taskStatus')" :disabled="!editable" size="sm" aria-label="Status" @update:model-value="save('status', $event)" /></dd>
                </div>
                <div>
                    <dt class="mb-1.5 text-label text-ink-3">Priority</dt>
                    <dd><Select :model-value="task.priority" :options="options('taskPriority')" :disabled="!editable" size="sm" aria-label="Priority" @update:model-value="save('priority', $event)" /></dd>
                </div>
                <div>
                    <dt class="mb-1.5 text-label text-ink-3">Assignee</dt>
                    <dd><Select :model-value="task.assignee_id ?? ''" :options="memberOptions" placeholder="Unassigned" :disabled="!editable" size="sm" aria-label="Assignee" @update:model-value="save('assignee_id', $event)" /></dd>
                </div>
                <div>
                    <dt class="mb-1.5 text-label text-ink-3">Due date</dt>
                    <dd><Input :model-value="task.due_date ?? ''" type="date" size="sm" :disabled="!editable" aria-label="Due date" @change="save('due_date', $event.target.value)" /></dd>
                </div>
                <div v-if="milestoneOptions.length">
                    <dt class="mb-1.5 text-label text-ink-3">Milestone</dt>
                    <dd><Select :model-value="task.milestone_id ?? ''" :options="milestoneOptions" placeholder="None" :disabled="!editable" size="sm" aria-label="Milestone" @update:model-value="save('milestone_id', $event)" /></dd>
                </div>
                <div>
                    <dt class="mb-1.5 text-label text-ink-3">Estimate</dt>
                    <dd><Input :model-value="estimateHours" type="number" min="0" step="0.5" suffix="hours" size="sm" :disabled="!editable" aria-label="Estimate in hours" @change="save('estimate_hours', $event.target.value)" /></dd>
                </div>
            </dl>

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-line bg-canvas/50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <Clock class="size-4 text-ink-3" />
                    <div>
                        <p class="text-body font-medium text-ink tabular">{{ formatDuration(task.tracked_seconds) }} tracked</p>
                        <p class="text-caption text-ink-3">{{ task.estimate_minutes ? `of ${formatDuration(task.estimate_minutes * 60)} estimated` : 'No estimate' }}</p>
                    </div>
                </div>
                <Button v-if="can('time.track') && page.props.timer?.task_id !== task.id" variant="secondary" size="sm" :icon="Play" @click="startTimer">Start timer</Button>
                <span v-else-if="page.props.timer?.task_id === task.id" class="inline-flex items-center gap-1.5 text-small font-medium text-danger"><span class="size-1.5 animate-pulse rounded-full bg-danger" />Timer running</span>
            </div>

            <div v-if="editable" class="flex items-center gap-3">
                <Eye class="size-4 text-ink-3" />
                <Switch :model-value="task.visible_to_client" label="Visible to client" description="Show this task and its status in the client portal." class="flex-1" @update:model-value="save('visible_to_client', $event)" />
            </div>

            <section aria-labelledby="task-description">
                <h3 id="task-description" class="mb-2 text-label text-ink-3">Description</h3>
                <Textarea v-model="description" :rows="4" autosize :readonly="!editable" placeholder="Add more detail…" aria-labelledby="task-description" @blur="saveDescription" />
            </section>

            <section aria-labelledby="task-files">
                <h3 id="task-files" class="mb-2 text-label text-ink-3">Attachments</h3>
                <FileList :files="task.attachments ?? []" :only="only" empty-title="No attachments" empty-description="Attach designs, briefs or screenshots." />
                <Dropzone v-if="can('files.manage')" :data="{ task_id: task.id }" :only="only" compact class="mt-3" />
            </section>

            <section aria-labelledby="task-comments">
                <h3 id="task-comments" class="mb-4 text-label text-ink-3">Comments</h3>
                <CommentThread :comments="task.comments ?? []" :action="route('tasks.comments.store', task.id)" :only="only" />
            </section>

            <section v-if="task.activity?.length" aria-labelledby="task-activity">
                <h3 id="task-activity" class="mb-4 text-label text-ink-3">Activity</h3>
                <ActivityFeed :items="task.activity" />
            </section>
        </div>
    </Drawer>
</template>
