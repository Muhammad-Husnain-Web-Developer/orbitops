import {
    ArrowRightLeft,
    BadgeCheck,
    BadgeDollarSign,
    Briefcase,
    CircleCheck,
    CircleDot,
    Clock,
    Flag,
    FolderKanban,
    MailPlus,
    MessageSquare,
    Paperclip,
    Plus,
    Send,
    UserCheck,
    UserPlus,
    Wallet,
} from '@lucide/vue';

const icons = {
    'project.created': [FolderKanban, 'text-accent-text bg-accent/10'],
    'project.updated': [FolderKanban, 'text-ink-2 bg-subtle'],
    'task.created': [Plus, 'text-info bg-info/10'],
    'task.completed': [CircleCheck, 'text-success bg-success/10'],
    'task.moved': [ArrowRightLeft, 'text-info bg-info/10'],
    'task.assigned': [UserPlus, 'text-info bg-info/10'],
    'comment.created': [MessageSquare, 'text-ink-2 bg-subtle'],
    'message.posted': [MessageSquare, 'text-warning bg-warning/10'],
    'milestone.approved': [BadgeCheck, 'text-success bg-success/10'],
    'milestone.changes_requested': [Flag, 'text-warning bg-warning/10'],
    'milestone.completed': [Flag, 'text-accent-text bg-accent/10'],
    'invoice.created': [Plus, 'text-ink-2 bg-subtle'],
    'invoice.sent': [Send, 'text-info bg-info/10'],
    'invoice.paid': [BadgeDollarSign, 'text-success bg-success/10'],
    'file.uploaded': [Paperclip, 'text-ink-2 bg-subtle'],
    'time.logged': [Clock, 'text-ink-2 bg-subtle'],
    'member.invited': [MailPlus, 'text-info bg-info/10'],
    'member.joined': [UserCheck, 'text-success bg-success/10'],
    'client.created': [Briefcase, 'text-accent-text bg-accent/10'],
    'expense.approved': [Wallet, 'text-success bg-success/10'],
    'expense.created': [Wallet, 'text-ink-2 bg-subtle'],
};

export function activityIcon(event) {
    const [icon, tone] = icons[event] ?? [CircleDot, 'text-ink-3 bg-subtle'];

    return { icon, tone };
}
