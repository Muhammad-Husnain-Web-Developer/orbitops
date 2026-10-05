import { onBeforeUnmount, ref } from 'vue';

const query = typeof window !== 'undefined' ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

export function prefersReducedMotion() {
    return Boolean(query?.matches);
}

export function useReducedMotion() {
    const reduced = ref(prefersReducedMotion());
    const update = () => (reduced.value = prefersReducedMotion());

    query?.addEventListener('change', update);
    onBeforeUnmount(() => query?.removeEventListener('change', update));

    return reduced;
}
