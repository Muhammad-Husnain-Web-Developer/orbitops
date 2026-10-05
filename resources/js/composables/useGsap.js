import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onBeforeUnmount, onMounted } from 'vue';

gsap.registerPlugin(ScrollTrigger);

export { gsap, ScrollTrigger };

/**
 * Scoped, responsive GSAP setup for marketing pages.
 *
 * `setup(conditions)` runs inside gsap.matchMedia(), so it re-runs when the
 * viewport crosses the desktop breakpoint or the user toggles reduced motion,
 * and every tween/ScrollTrigger is reverted when the page unmounts.
 */
export function useGsap(scope, setup) {
    let mm;

    onMounted(() => {
        mm = gsap.matchMedia(scope.value);

        mm.add(
            {
                motion: '(prefers-reduced-motion: no-preference)',
                reduced: '(prefers-reduced-motion: reduce)',
                desktop: '(min-width: 1024px)',
            },
            (context) => setup(context.conditions, context),
        );

        // Web fonts and images change layout; re-measure trigger positions once settled.
        document.fonts?.ready.then(() => ScrollTrigger.refresh());
    });

    onBeforeUnmount(() => mm?.revert());
}

/** Fade-and-rise for blocks marked with data-reveal, batched for performance. */
export function revealOnScroll(selector = '[data-reveal]') {
    const targets = gsap.utils.toArray(selector);
    if (!targets.length) return;

    gsap.set(targets, { opacity: 0, y: 28 });

    ScrollTrigger.batch(targets, {
        start: 'top 88%',
        once: true,
        onEnter: (batch) => gsap.to(batch, { opacity: 1, y: 0, duration: 0.9, ease: 'expo.out', stagger: 0.08, overwrite: true }),
    });
}
