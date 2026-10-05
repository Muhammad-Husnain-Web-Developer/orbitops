import { reactive } from 'vue';
import { api } from '@/lib/api';

/*
 * Client/project/member options for create & edit forms. Fetched once per
 * workspace visit and refreshed after anything is created.
 */
const state = reactive({ clients: [], projects: [], members: [], loaded: false, loading: false, workspaceId: null });
let pending = null;

export function useLookups() {
    async function load(workspaceId, { force = false } = {}) {
        if (state.loaded && !force && state.workspaceId === workspaceId) return state;
        if (pending && !force) return pending;

        state.loading = true;
        pending = api
            .get(route('lookups'))
            .then((data) => {
                Object.assign(state, data, { loaded: true, workspaceId });
                return state;
            })
            .finally(() => {
                state.loading = false;
                pending = null;
            });

        return pending;
    }

    function invalidate() {
        state.loaded = false;
    }

    return { lookups: state, load, invalidate };
}
