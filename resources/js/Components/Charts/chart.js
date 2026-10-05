import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Round a range to a "nice" number (1, 2, 5 × 10^n). */
function niceNumber(range, round) {
    const exponent = Math.floor(Math.log10(range));
    const fraction = range / 10 ** exponent;
    let nice;

    if (round) {
        nice = fraction < 1.5 ? 1 : fraction < 3 ? 2 : fraction < 7 ? 5 : 10;
    } else {
        nice = fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10;
    }

    return nice * 10 ** exponent;
}

/** Clean y-axis ticks from zero up to a rounded maximum. */
export function niceTicks(max, count = 4) {
    if (!max || max <= 0) return [0, 1];

    const step = niceNumber(max / count, true);
    const top = Math.ceil(max / step) * step;
    const ticks = [];

    for (let value = 0; value <= top + step / 1000; value += step) {
        ticks.push(Number(value.toFixed(10)));
    }

    return ticks;
}

/** Monotone cubic interpolation (no overshoot), so smoothed lines never lie about the data. */
export function monotonePath(points) {
    const n = points.length;
    if (!n) return '';
    if (n === 1) return `M${points[0][0]},${points[0][1]}`;

    const dx = [];
    const slopes = [];

    for (let i = 0; i < n - 1; i++) {
        dx[i] = points[i + 1][0] - points[i][0];
        slopes[i] = dx[i] === 0 ? 0 : (points[i + 1][1] - points[i][1]) / dx[i];
    }

    const tangents = new Array(n);
    tangents[0] = slopes[0];
    tangents[n - 1] = slopes[n - 2];

    for (let i = 1; i < n - 1; i++) {
        if (slopes[i - 1] * slopes[i] <= 0) {
            tangents[i] = 0;
        } else {
            tangents[i] = (3 * (dx[i - 1] + dx[i])) / ((2 * dx[i] + dx[i - 1]) / slopes[i - 1] + (dx[i] + 2 * dx[i - 1]) / slopes[i]);
        }
    }

    let path = `M${points[0][0]},${points[0][1]}`;

    for (let i = 0; i < n - 1; i++) {
        const [x0, y0] = points[i];
        const [x1, y1] = points[i + 1];
        const h = dx[i] / 3;

        path += `C${x0 + h},${y0 + tangents[i] * h} ${x1 - h},${y1 - tangents[i + 1] * h} ${x1},${y1}`;
    }

    return path;
}

/** Track an element's width so SVG charts render crisply at any size. */
export function useWidth() {
    const el = ref(null);
    const width = ref(0);
    let observer;

    onMounted(() => {
        width.value = el.value?.clientWidth ?? 0;
        observer = new ResizeObserver(([entry]) => (width.value = Math.floor(entry.contentRect.width)));
        observer.observe(el.value);
    });

    onBeforeUnmount(() => observer?.disconnect());

    return { el, width };
}

/** Keep a tooltip inside its chart container. */
export function tooltipPosition(x, width, tooltipWidth = 180) {
    const left = x + 14 + tooltipWidth > width ? x - tooltipWidth - 14 : x + 14;

    return Math.max(0, left);
}
