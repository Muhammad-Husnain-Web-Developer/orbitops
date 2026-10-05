import { router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
import { debounce } from '@/lib/debounce';

/**
 * Keeps list filters in the URL and reloads only the props that depend on them.
 * Returns `loading` so tables can dim while results refresh (no layout jump).
 */
export function useFilters(initial, { route: routeName, params = {}, only = [], wait = 0 } = {}) {
    const filters = reactive({ ...initial });
    const loading = ref(false);

    const apply = () => {
        const query = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null && value !== undefined && !(Array.isArray(value) && !value.length)));

        router.get(route(routeName, params), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: only.length ? only : undefined,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        });
    };

    const scheduled = wait ? debounce(apply, wait) : apply;

    watch(filters, () => scheduled(), { deep: true });

    function reset(defaults = {}) {
        Object.keys(filters).forEach((key) => (filters[key] = defaults[key] ?? ''));
    }

    function sortBy(key) {
        if (filters.sort === key) {
            filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
        } else {
            filters.sort = key;
            filters.direction = ['name', 'title'].includes(key) ? 'asc' : 'desc';
        }
    }

    return { filters, loading, reset, sortBy, apply };
}
