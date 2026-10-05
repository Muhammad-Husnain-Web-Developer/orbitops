import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { api } from '@/lib/api';

const STORAGE_KEY = 'orbitops:theme';
const preference = ref(readStored() ?? 'dark');
const systemDark = typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function readStored() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function apply() {
    const dark = preference.value === 'dark' || (preference.value === 'system' && systemDark?.matches);
    const root = document.documentElement;

    root.classList.toggle('dark', dark);
    root.dataset.theme = dark ? 'dark' : 'light';
}

systemDark?.addEventListener('change', () => preference.value === 'system' && apply());

export function useTheme() {
    const page = usePage();

    const isDark = computed(() => preference.value === 'dark' || (preference.value === 'system' && systemDark?.matches));

    function setTheme(value, { persist = true } = {}) {
        preference.value = value;

        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch {
            // Private mode: the choice still applies for this session.
        }

        apply();

        if (persist && page.props.auth?.user) {
            api.put(route('settings.appearance.update'), { theme: value }).catch(() => {});
        }
    }

    function toggle() {
        setTheme(isDark.value ? 'light' : 'dark');
    }

    return { preference, isDark, setTheme, toggle };
}
