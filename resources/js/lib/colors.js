/*
 * Project/workspace colour keys map to accent swatches. Keeping them as keys
 * (not hex) lets light and dark themes pick their own step.
 */
export const swatches = {
    violet: 'bg-[#6d5dfc] dark:bg-[#8b7cff]',
    blue: 'bg-[#2f6fed] dark:bg-[#5b8cff]',
    cyan: 'bg-[#0891b2] dark:bg-[#22d3ee]',
    emerald: 'bg-[#059669] dark:bg-[#34d399]',
    amber: 'bg-[#d97706] dark:bg-[#fbbf24]',
    rose: 'bg-[#e11d48] dark:bg-[#fb7185]',
};

export const colorKeys = Object.keys(swatches);

/*
 * Solid fills for white text (initials, project codes). Dark enough for ≥5:1
 * contrast with white in both themes, unlike the lighter dark-mode swatches.
 */
export const solidSwatches = {
    violet: 'bg-[#5b4af0]',
    blue: 'bg-[#2459c9]',
    cyan: 'bg-[#0e7490]',
    emerald: 'bg-[#047857]',
    amber: 'bg-[#b45309]',
    rose: 'bg-[#be123c]',
};

export function solidSwatch(key) {
    return solidSwatches[key] ?? solidSwatches.violet;
}

export function swatch(key) {
    return swatches[key] ?? swatches.violet;
}

/** Validated categorical chart slots, in fixed order (never cycled past six). */
export const chartSlots = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)', 'var(--chart-6)'];

/** Stable avatar tint from a name, so people keep their colour everywhere. */
const avatarTints = [
    'bg-violet-500/15 text-violet-800 dark:text-violet-300',
    'bg-sky-500/15 text-sky-800 dark:text-sky-300',
    'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300',
    'bg-amber-500/15 text-amber-800 dark:text-amber-300',
    'bg-rose-500/15 text-rose-800 dark:text-rose-300',
    'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300',
    'bg-fuchsia-500/15 text-fuchsia-800 dark:text-fuchsia-300',
];

export function avatarTint(name = '') {
    let hash = 0;

    for (const char of name) {
        hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
    }

    return avatarTints[hash % avatarTints.length];
}
