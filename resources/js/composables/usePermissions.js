import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * UI-only permission checks to hide actions the user cannot take.
 * The server authorizes every request regardless.
 */
export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => new Set(page.props.access?.permissions ?? []));
    const role = computed(() => page.props.access?.role ?? null);

    const can = (permission) => permissions.value.has(permission);
    const canAny = (...list) => list.some(can);

    return { can, canAny, role };
}
