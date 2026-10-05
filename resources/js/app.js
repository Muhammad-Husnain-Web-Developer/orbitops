import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { toast } from '@/composables/useToast';

const appName = import.meta.env.VITE_APP_NAME || 'OrbitOps';

const pages = import.meta.glob('./Pages/**/*.vue');

// Layouts load per area, so marketing visitors never download the app shell.
const layouts = {
    marketing: () => import('./Layouts/MarketingLayout.vue'),
    auth: () => import('./Layouts/AuthLayout.vue'),
    app: () => import('./Layouts/AppLayout.vue'),
    settings: () => import('./Layouts/SettingsLayout.vue'),
    portal: () => import('./Layouts/PortalLayout.vue'),
};

function layoutsFor(name) {
    if (name.startsWith('Marketing/')) return ['marketing'];
    if (/^(Auth|Onboarding|Invitations)\//.test(name)) return ['auth'];
    if (name.startsWith('Portal/')) return ['portal'];
    if (name.startsWith('Settings/')) return ['app', 'settings'];
    if (name.startsWith('Errors/')) return [];

    return ['app'];
}

async function resolvePage(name) {
    const load = pages[`./Pages/${name}.vue`];

    if (!load) {
        throw new Error(`Unknown page component: ${name}`);
    }

    const page = await load();
    const keys = layoutsFor(name);

    if (page.default.layout === undefined && keys.length) {
        const modules = await Promise.all(keys.map((key) => layouts[key]()));
        page.default.layout = modules.map((module) => module.default);
    }

    return page;
}

function showFlashToast(flash) {
    if (flash?.toast?.message) {
        toast(flash.toast.message, { type: flash.toast.type, description: flash.toast.description });
    }
}

createInertiaApp({
    title: (title) => (!title ? appName : title.includes(appName) ? title : `${title} · ${appName}`),
    resolve: resolvePage,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        showFlashToast(props.initialPage.flash);
    },
    progress: {
        color: 'var(--accent)',
        delay: 160,
        showSpinner: false,
    },
});

router.on('flash', (event) => showFlashToast(event.detail.flash));
