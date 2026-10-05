import { onMounted, ref, watch } from 'vue';
import { prefersReducedMotion } from './useReducedMotion';

/** Animate a number from its previous value to the next (respects reduced motion). */
export function useCountUp(source, { duration = 900 } = {}) {
    const display = ref(0);
    let frame;

    function animate(to, from = display.value) {
        cancelAnimationFrame(frame);

        if (prefersReducedMotion() || duration === 0) {
            display.value = to;
            return;
        }

        const start = performance.now();

        const tick = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 4);
            display.value = from + (to - from) * eased;

            if (t < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);
    }

    onMounted(() => animate(Number(source()) || 0, 0));
    watch(source, (value) => animate(Number(value) || 0));

    return display;
}
