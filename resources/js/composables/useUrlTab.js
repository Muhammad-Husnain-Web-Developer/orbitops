import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

/** A tab selection mirrored into `?tab=` without a server round-trip, so tabs are linkable. */
export function useUrlTab(initial) {
    const page = usePage();
    const tab = ref(initial);

    watch(tab, (value) => {
        const url = new URL(page.url, window.location.origin);
        url.searchParams.set('tab', value);
        router.replace({ url: `${url.pathname}${url.search}`, preserveState: true, preserveScroll: true });
    });

    return tab;
}
