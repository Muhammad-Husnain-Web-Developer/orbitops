import { ref, watch } from 'vue';
import { api } from '@/lib/api';

const cache = new Map();

/**
 * Task options for one project, fetched when the project changes and cached
 * for the page session (timer bar and time entry form share it).
 */
export function useProjectTasks(projectId) {
    const tasks = ref([]);
    const loading = ref(false);

    watch(
        projectId,
        async (id) => {
            if (!id) {
                tasks.value = [];
                return;
            }

            if (!cache.has(id)) {
                loading.value = true;
                cache.set(id, api.get(route('lookups'), { params: { project: id } }).then((data) => data.tasks).catch(() => {
                    cache.delete(id);
                    return [];
                }));
            }

            const result = await cache.get(id);
            loading.value = false;

            // Ignore a slow response for a project that is no longer selected.
            if (projectId() === id) tasks.value = result;
        },
        { immediate: true },
    );

    return { tasks, loading };
}
