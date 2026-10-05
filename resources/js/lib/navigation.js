import {
    Activity,
    BarChart3,
    Briefcase,
    Calendar,
    CheckSquare,
    Clock,
    FileText,
    FolderKanban,
    FolderOpen,
    LayoutGrid,
    Settings,
    Users,
    Wallet,
} from '@lucide/vue';

/**
 * The single source of truth for app navigation, used by the sidebar,
 * the mobile drawer and the command palette. `permission` hides items the
 * current role cannot use (the server still authorizes every request).
 */
export const primaryNav = [
    { label: 'Overview', route: 'dashboard', match: 'dashboard', icon: LayoutGrid },
    { label: 'Clients', route: 'clients.index', match: 'clients.*', icon: Briefcase, permission: 'clients.view' },
    { label: 'Projects', route: 'projects.index', match: 'projects.*', icon: FolderKanban, permission: 'projects.view' },
    { label: 'Tasks', route: 'tasks.index', match: 'tasks.*', icon: CheckSquare, permission: 'tasks.view', count: 'my_tasks' },
    { label: 'Calendar', route: 'calendar', match: 'calendar', icon: Calendar, permission: 'tasks.view' },
    { label: 'Time', route: 'time.index', match: 'time.*', icon: Clock, permission: 'time.track' },
    { label: 'Invoices', route: 'invoices.index', match: 'invoices.*', icon: FileText, permission: 'invoices.view', count: 'overdue_invoices', countTone: 'danger' },
    { label: 'Expenses', route: 'expenses.index', match: 'expenses.*', icon: Wallet, permission: 'expenses.view' },
    { label: 'Files', route: 'files.index', match: 'files.*', icon: FolderOpen, permission: 'files.view' },
    { label: 'Reports', route: 'reports', match: 'reports', icon: BarChart3, permission: 'reports.view' },
];

export const workspaceNav = [
    { label: 'Team', route: 'team.index', match: 'team.*', icon: Users, permission: 'team.view' },
    { label: 'Activity', route: 'activity', match: 'activity', icon: Activity, permission: 'activity.view' },
];

export const settingsNav = { label: 'Settings', route: 'settings.general', match: 'settings.*', icon: Settings };

export function visibleItems(items, can) {
    return items.filter((item) => !item.permission || can(item.permission));
}
