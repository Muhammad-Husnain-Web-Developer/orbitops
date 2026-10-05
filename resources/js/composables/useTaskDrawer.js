import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/**
 * The task drawer is addressable by URL (`?task=12`) so it can be deep-linked
 * from notifications and search. Opening it is a partial reload of `activeTask`.
 */
export function useTaskDrawer(activeTask) {
    const page = usePage();
    const pendingId = ref(null);

    const open = computed({
        get: () => Boolean(pendingId.value || activeTask()),
        set: (value) => !value && close(),
    });

    const loading = computed(() => Boolean(pendingId.value) && activeTask()?.id !== pendingId.value);

    function visit(params) {
        const url = new URL(page.url, window.location.origin);
        const query = Object.fromEntries(url.searchParams);

        router.get(url.pathname, { ...query, ...params }, {
            preserveState: true,
            preserveScroll: true,
            only: ['activeTask'],
            onFinish: () => (pendingId.value = null),
        });
    }

    function openTask(id) {
        pendingId.value = id;
        visit({ task: id });
    }

    function close() {
        pendingId.value = null;
        visit({ task: undefined });
    }

    return { open, loading, openTask, close };
}
