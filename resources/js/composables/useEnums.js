import { usePage } from '@inertiajs/vue3';

/** Labels and tones for backend enums, shared once per session as Inertia "once" props. */
export function useEnums() {
    const page = usePage();

    const options = (group) => page.props.enums?.[group] ?? [];

    const option = (group, value) => options(group).find((item) => item.value === value) ?? { value, label: value ?? '—', tone: 'neutral' };

    return { options, option, label: (group, value) => option(group, value).label };
}
