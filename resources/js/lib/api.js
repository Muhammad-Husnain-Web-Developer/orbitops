import { http } from '@inertiajs/vue3';

/**
 * Small JSON helper on top of Inertia's HTTP client (which handles the XSRF
 * cookie). Used only for non-navigational requests: search, notification feed,
 * preference toggles. Page data always arrives as Inertia props.
 */
export async function api(method, url, { data, params, signal } = {}) {
    const query = params ? `?${new URLSearchParams(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''))}` : '';

    const response = await http.getClient().request({
        method,
        url: `${url}${query}`,
        data,
        signal,
        headers: { Accept: 'application/json' },
    });

    return response.data ? JSON.parse(response.data) : null;
}

api.get = (url, options) => api('get', url, options);
api.post = (url, data, options) => api('post', url, { ...options, data });
api.put = (url, data, options) => api('put', url, { ...options, data });
api.delete = (url, options) => api('delete', url, options);
