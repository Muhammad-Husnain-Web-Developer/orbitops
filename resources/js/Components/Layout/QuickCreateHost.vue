<script setup>
import { computed } from 'vue';
import ClientFormModal from '@/Components/Clients/ClientFormModal.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';
import TaskFormModal from '@/Components/Tasks/TaskFormModal.vue';
import TimeEntryModal from '@/Components/Time/TimeEntryModal.vue';
import { closeQuickCreate, quickCreate } from '@/composables/useQuickCreate';

const bind = (kind) =>
    computed({
        get: () => quickCreate.kind === kind,
        set: (value) => !value && closeQuickCreate(),
    });

const client = bind('client');
const project = bind('project');
const task = bind('task');
const time = bind('time');
</script>

<template>
    <ClientFormModal v-model:open="client" />
    <ProjectFormModal v-model:open="project" :defaults="quickCreate.defaults" />
    <TaskFormModal v-model:open="task" :defaults="quickCreate.defaults" :milestones="quickCreate.defaults.milestones ?? []" />
    <TimeEntryModal v-model:open="time" :defaults="quickCreate.defaults" />
</template>
