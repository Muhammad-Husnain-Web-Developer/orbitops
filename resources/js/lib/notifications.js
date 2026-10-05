import { AlarmClock, AtSign, BadgeDollarSign, Bell, CircleCheck, Flag, MailPlus, MessageSquare, UserPlus } from '@lucide/vue';
import { toDate } from './format';

/** Icon and tone per notification type, shared by the popover and the full page. */
export const notificationTypes = {
    task_assigned: { icon: UserPlus, tone: 'text-info bg-info/10', label: 'Assignments' },
    task_completed: { icon: CircleCheck, tone: 'text-success bg-success/10', label: 'Completed' },
    project_update: { icon: Flag, tone: 'text-accent-text bg-accent/10', label: 'Projects' },
    invoice_paid: { icon: BadgeDollarSign, tone: 'text-success bg-success/10', label: 'Payments' },
    invoice_overdue: { icon: AlarmClock, tone: 'text-danger bg-danger/10', label: 'Overdue' },
    client_comment: { icon: MessageSquare, tone: 'text-warning bg-warning/10', label: 'Client messages' },
    mention: { icon: AtSign, tone: 'text-accent-text bg-accent/10', label: 'Mentions' },
    team_invitation: { icon: MailPlus, tone: 'text-info bg-info/10', label: 'Invitations' },
};

export function notificationMeta(type) {
    return notificationTypes[type] ?? { icon: Bell, tone: 'text-ink-2 bg-subtle', label: 'Other' };
}

/** Group notifications under Today / Yesterday / Earlier this week / Older headings. */
export function groupByDay(items) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const day = 86400000;

    const buckets = [
        ['Today', (d) => d >= today],
        ['Yesterday', (d) => d >= today - day],
        ['Earlier this week', (d) => d >= today - 6 * day],
        ['Older', () => true],
    ];

    const groups = new Map();

    for (const item of items) {
        const date = toDate(item.created_at);
        const [label] = buckets.find(([, test]) => test(date));
        if (!groups.has(label)) groups.set(label, []);
        groups.get(label).push(item);
    }

    return [...groups].map(([label, entries]) => ({ label, items: entries }));
}
