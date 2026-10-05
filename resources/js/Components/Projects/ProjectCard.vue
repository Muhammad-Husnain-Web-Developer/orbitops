<script setup>
import { Link } from '@inertiajs/vue3';
import { CalendarDays, CheckSquare } from '@lucide/vue';
import AvatarStack from '@/Components/UI/AvatarStack.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { swatch } from '@/lib/colors';
import { daysUntil, dueLabel } from '@/lib/format';

defineProps({
    project: { type: Object, required: true },
});
</script>

<template>
    <Link :href="route('projects.show', project.id)" class="group relative flex flex-col overflow-hidden rounded-xl border border-line bg-surface p-4 shadow-card transition-[border-color,transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-raised">
        <span class="absolute inset-x-0 top-0 h-0.5 opacity-80" :class="swatch(project.color)" aria-hidden="true" />
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-body font-semibold text-ink">{{ project.name }}</p>
                <p class="truncate text-small text-ink-3">{{ project.client?.name ?? 'Internal project' }}</p>
            </div>
            <StatusBadge group="projectStatus" :value="project.status" size="sm" />
        </div>
        <div class="mt-4">
            <div class="mb-1.5 flex items-center justify-between text-caption">
                <span class="flex items-center gap-1 text-ink-3"><CheckSquare class="size-3" />{{ project.completed_tasks_count ?? 0 }} / {{ project.tasks_count ?? 0 }} tasks</span>
                <span class="font-medium text-ink-2 tabular">{{ project.progress ?? 0 }}%</span>
            </div>
            <ProgressBar :value="project.progress ?? 0" size="sm" :label="`${project.name} progress`" />
        </div>
        <div class="mt-4 flex items-center justify-between">
            <AvatarStack :users="project.members ?? []" size="sm" :max="4" />
            <span v-if="project.due_date" class="flex items-center gap-1 text-caption" :class="daysUntil(project.due_date) < 0 ? 'text-danger' : 'text-ink-3'">
                <CalendarDays class="size-3" />{{ dueLabel(project.due_date) }}
            </span>
        </div>
    </Link>
</template>
