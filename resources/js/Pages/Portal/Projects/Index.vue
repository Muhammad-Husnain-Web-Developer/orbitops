<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, Flag, FolderKanban } from '@lucide/vue';
import { computed } from 'vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ProgressRing from '@/Components/UI/ProgressRing.vue';
import StatusBadge from '@/Components/UI/StatusBadge.vue';
import { swatch } from '@/lib/colors';
import { formatDate } from '@/lib/format';

const props = defineProps({
    projects: { type: Array, required: true },
});

const active = computed(() => props.projects.filter((project) => !project.completed_at));
const finished = computed(() => props.projects.filter((project) => project.completed_at));
</script>

<template>
    <Head title="Projects" />

    <header class="mb-6">
        <h1 class="text-h2 text-ink sm:text-[1.75rem]">Projects</h1>
        <p class="mt-1 text-body text-ink-3">Progress, milestones and files for everything we're working on together.</p>
    </header>

    <Card v-if="!projects.length">
        <EmptyState :icon="FolderKanban" title="No projects yet" description="Your projects will appear here as soon as work begins." />
    </Card>

    <template v-for="group in [{ label: 'In progress', items: active }, { label: 'Completed', items: finished }]" :key="group.label">
        <section v-if="group.items.length" class="mb-8" :aria-label="group.label">
            <h2 class="mb-3 text-label text-ink-3">{{ group.label }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <Link v-for="project in group.items" :key="project.id" :href="route('portal.projects.show', project.id)" class="flex gap-4 rounded-xl border border-line bg-surface p-5 shadow-card transition-colors hover:border-line-strong">
                    <ProgressRing :value="project.progress" :size="56" class="shrink-0" :label="`${project.name} progress`" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="flex min-w-0 items-center gap-2 text-body font-semibold text-ink"><span class="size-2 shrink-0 rounded-full" :class="swatch(project.color)" /><span class="truncate">{{ project.name }}</span></p>
                            <StatusBadge group="projectStatus" :value="project.status" size="sm" />
                        </div>
                        <p v-if="project.description" class="mt-1 line-clamp-2 text-small text-ink-3">{{ project.description }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-caption text-ink-3">
                            <span class="flex items-center gap-1"><Flag class="size-3.5" />{{ project.milestones_done }}/{{ project.milestones_total }} milestones</span>
                            <span v-if="project.completed_at" class="flex items-center gap-1"><CalendarClock class="size-3.5" />Delivered {{ formatDate(project.completed_at) }}</span>
                            <span v-else-if="project.due_date" class="flex items-center gap-1"><CalendarClock class="size-3.5" />Due {{ formatDate(project.due_date) }}</span>
                            <span v-if="project.lead" class="flex items-center gap-1.5"><Avatar :user="project.lead" size="xs" decorative />{{ project.lead.name }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </section>
    </template>
</template>
