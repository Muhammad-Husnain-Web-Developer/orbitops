<script setup>
import { CalendarDays, MessageSquare, Paperclip } from '@lucide/vue';
import { computed } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { useEnums } from '@/composables/useEnums';
import { swatch } from '@/lib/colors';
import { daysUntil, formatDate } from '@/lib/format';

const props = defineProps({
    task: { type: Object, required: true },
    showProject: { type: Boolean, default: true },
});

defineEmits(['open']);

const { option } = useEnums();
const priority = computed(() => option('taskPriority', props.task.priority));
const due = computed(() => (props.task.due_date ? daysUntil(props.task.due_date) : null));
const priorityTones = { danger: 'bg-danger/10 text-danger', warning: 'bg-warning/10 text-warning', info: 'bg-info/10 text-info', neutral: 'bg-subtle text-ink-3' };
</script>

<template>
    <article
        :data-id="task.id"
        class="group relative cursor-grab rounded-xl border border-line bg-surface p-3 shadow-card transition-[border-color,box-shadow,transform] duration-150 hover:border-line-strong hover:shadow-raised active:cursor-grabbing"
        :class="task.status === 'done' ? 'opacity-75' : ''"
    >
        <div class="mb-1.5 flex items-center gap-1.5 text-caption text-ink-3">
            <span v-if="showProject && task.project" class="size-1.5 shrink-0 rounded-full" :class="swatch(task.project.color)" aria-hidden="true" />
            <span class="font-mono text-[0.6875rem] tracking-tight">{{ task.key }}</span>
            <span v-if="showProject && task.project" class="truncate">· {{ task.project.name }}</span>
        </div>
        <button type="button" class="block w-full text-left text-body leading-snug font-medium text-ink after:absolute after:inset-0 focus-visible:outline-none" :class="task.status === 'done' ? 'line-through decoration-ink-3' : ''" @click="$emit('open', task)">
            {{ task.title }}
        </button>
        <div class="mt-3 flex items-center gap-2">
            <span class="rounded-full px-1.5 py-px text-[0.6875rem] font-medium" :class="priorityTones[priority.tone]">{{ priority.label }}</span>
            <span v-if="task.due_date" class="inline-flex items-center gap-1 text-caption" :class="due < 0 && task.status !== 'done' ? 'font-medium text-danger' : due <= 2 && task.status !== 'done' ? 'text-warning' : 'text-ink-3'">
                <CalendarDays class="size-3" aria-hidden="true" />{{ formatDate(task.due_date, 'short') }}
            </span>
            <span class="ml-auto flex items-center gap-2 text-caption text-ink-3">
                <span v-if="task.comments_count" class="inline-flex items-center gap-0.5"><MessageSquare class="size-3" aria-hidden="true" />{{ task.comments_count }}<span class="sr-only">comments</span></span>
                <span v-if="task.attachments_count" class="inline-flex items-center gap-0.5"><Paperclip class="size-3" aria-hidden="true" />{{ task.attachments_count }}<span class="sr-only">files</span></span>
                <Avatar v-if="task.assignee" :user="task.assignee" size="xs" />
            </span>
        </div>
    </article>
</template>
