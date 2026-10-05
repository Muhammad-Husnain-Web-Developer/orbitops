<?php

namespace App\Support;

/**
 * The single list of workspace permissions, grouped for the settings matrix.
 * Permissions are global; roles are created per workspace (Spatie teams).
 */
class PermissionCatalog
{
    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'clients' => ['label' => 'Clients', 'permissions' => [
                'clients.view' => 'View clients',
                'clients.manage' => 'Create & edit clients',
                'clients.delete' => 'Delete clients',
            ]],
            'projects' => ['label' => 'Projects', 'permissions' => [
                'projects.view' => 'View projects',
                'projects.manage' => 'Create & edit projects',
                'projects.delete' => 'Delete projects',
            ]],
            'tasks' => ['label' => 'Tasks', 'permissions' => [
                'tasks.view' => 'View tasks',
                'tasks.manage' => 'Create & edit tasks',
                'tasks.delete' => 'Delete tasks',
            ]],
            'time' => ['label' => 'Time tracking', 'permissions' => [
                'time.track' => 'Track own time',
                'time.view_all' => "View everyone's time",
            ]],
            'invoices' => ['label' => 'Invoices', 'permissions' => [
                'invoices.view' => 'View invoices',
                'invoices.manage' => 'Create, edit & send invoices',
                'invoices.delete' => 'Delete invoices',
            ]],
            'expenses' => ['label' => 'Expenses', 'permissions' => [
                'expenses.view' => 'View expenses',
                'expenses.manage' => 'Submit & edit expenses',
                'expenses.approve' => 'Approve expenses',
            ]],
            'files' => ['label' => 'Files', 'permissions' => [
                'files.view' => 'View files',
                'files.manage' => 'Upload & delete files',
            ]],
            'insights' => ['label' => 'Insights', 'permissions' => [
                'reports.view' => 'View reports',
                'activity.view' => 'View activity log',
            ]],
            'team' => ['label' => 'Team', 'permissions' => [
                'team.view' => 'View team',
                'team.manage' => 'Invite & manage members',
            ]],
            'workspace' => ['label' => 'Workspace', 'permissions' => [
                'workspace.settings' => 'Manage workspace settings',
                'workspace.roles' => 'Manage roles & permissions',
                'workspace.billing' => 'Manage billing',
                'workspace.delete' => 'Delete workspace',
            ]],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return collect(self::groups())->flatMap(fn ($group) => array_keys($group['permissions']))->values()->all();
    }
}
