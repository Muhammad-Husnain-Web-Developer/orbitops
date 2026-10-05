/*
 * Formatting helpers shared by every screen. Intl formatters are cached
 * because they are comparatively expensive to construct.
 */

const cache = new Map();

function formatter(key, factory) {
    if (!cache.has(key)) {
        cache.set(key, factory());
    }

    return cache.get(key);
}

/** Parse API dates. Date-only strings are treated as local calendar dates. */
export function toDate(value) {
    if (!value) return null;
    if (value instanceof Date) return value;

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [y, m, d] = value.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    return new Date(value);
}

export function toDateInput(date = new Date()) {
    const d = toDate(date);
    const pad = (n) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export function formatMoney(amount, currency = 'USD', { compact = false, decimals } = {}) {
    const value = Number(amount ?? 0);
    const digits = decimals ?? (compact ? 1 : Number.isInteger(value) ? 0 : 2);

    return formatter(`money:${currency}:${compact}:${digits}`, () =>
        new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            notation: compact ? 'compact' : 'standard',
            minimumFractionDigits: compact ? 0 : digits,
            maximumFractionDigits: digits,
        }),
    ).format(value);
}

export function formatNumber(value, { compact = false, decimals = 0 } = {}) {
    return formatter(`num:${compact}:${decimals}`, () =>
        new Intl.NumberFormat(undefined, {
            notation: compact ? 'compact' : 'standard',
            maximumFractionDigits: decimals,
        }),
    ).format(Number(value ?? 0));
}

export function formatPercent(value, decimals = 0) {
    return `${Number(value ?? 0).toFixed(decimals)}%`;
}

const dateStyles = {
    short: { month: 'short', day: 'numeric' },
    medium: { month: 'short', day: 'numeric', year: 'numeric' },
    long: { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' },
    month: { month: 'long', year: 'numeric' },
    monthShort: { month: 'short' },
    weekday: { weekday: 'short' },
    time: { hour: 'numeric', minute: '2-digit' },
    datetime: { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' },
};

export function formatDate(value, style = 'medium') {
    const date = toDate(value);
    if (!date || Number.isNaN(date.getTime())) return '—';

    return formatter(`date:${style}`, () => new Intl.DateTimeFormat(undefined, dateStyles[style])).format(date);
}

const units = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

export function formatRelative(value) {
    const date = toDate(value);
    if (!date) return '';

    const seconds = Math.round((date.getTime() - Date.now()) / 1000);
    const abs = Math.abs(seconds);

    if (abs < 45) return 'just now';

    const rtf = formatter('rtf', () => new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' }));

    for (const [unit, size] of units) {
        if (abs >= size || unit === 'minute') {
            return rtf.format(Math.round(seconds / size), unit);
        }
    }

    return '';
}

/** "3h 20m" or, with clock, "03:20:00". */
export function formatDuration(totalSeconds, { clock = false, compact = false } = {}) {
    const seconds = Math.max(0, Math.floor(Number(totalSeconds) || 0));
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;

    if (clock) {
        return [h, m, s].map((n) => String(n).padStart(2, '0')).join(':');
    }

    if (compact) {
        return h >= 10 ? `${Math.round(seconds / 3600)}h` : `${(seconds / 3600).toFixed(1).replace(/\.0$/, '')}h`;
    }

    if (h && m) return `${h}h ${m}m`;
    if (h) return `${h}h`;

    return `${m}m`;
}

export function formatBytes(bytes) {
    const value = Number(bytes) || 0;
    if (value < 1024) return `${value} B`;

    const unitsList = ['KB', 'MB', 'GB'];
    let size = value / 1024;
    let index = 0;

    while (size >= 1024 && index < unitsList.length - 1) {
        size /= 1024;
        index++;
    }

    return `${size.toFixed(size >= 10 ? 0 : 1)} ${unitsList[index]}`;
}

export function daysUntil(value) {
    const date = toDate(value);
    if (!date) return null;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return Math.round((date.getTime() - today.getTime()) / 86400000);
}

/** Human due-date phrase: "Due today", "3 days overdue", "Due in 5 days". */
export function dueLabel(value) {
    const days = daysUntil(value);
    if (days === null) return 'No due date';
    if (days === 0) return 'Due today';
    if (days === 1) return 'Due tomorrow';
    if (days < 0) return `${Math.abs(days)} day${days === -1 ? '' : 's'} overdue`;

    return days <= 14 ? `Due in ${days} days` : `Due ${formatDate(value, 'short')}`;
}

export function initials(name = '') {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export function pluralize(count, singular, plural = `${singular}s`) {
    return `${formatNumber(count)} ${count === 1 ? singular : plural}`;
}
