<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Support\PermissionCatalog;

enum WorkspaceRole: string
{
    use HasOptions;

    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';
    case Client = 'client';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Owner => 'accent',
            self::Admin => 'info',
            self::Manager => 'success',
            self::Member => 'neutral',
            self::Client => 'warning',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full access, including billing and deleting the workspace.',
            self::Admin => 'Manages the workspace, team and finances.',
            self::Manager => 'Runs clients, projects, invoices and expenses.',
            self::Member => 'Works on projects, tasks and tracks time.',
            self::Client => 'Access to the client portal only.',
        };
    }

    /**
     * Roles that can be assigned to internal team members.
     *
     * @return list<self>
     */
    public static function team(): array
    {
        return [self::Owner, self::Admin, self::Manager, self::Member];
    }

    /**
     * Default permissions a new workspace grants to this role.
     *
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Owner => PermissionCatalog::all(),
            self::Admin => array_values(array_diff(PermissionCatalog::all(), ['workspace.delete'])),
            self::Manager => [
                'clients.view', 'clients.manage',
                'projects.view', 'projects.manage', 'projects.delete',
                'tasks.view', 'tasks.manage', 'tasks.delete',
                'time.track', 'time.view_all',
                'invoices.view', 'invoices.manage',
                'expenses.view', 'expenses.manage', 'expenses.approve',
                'files.view', 'files.manage',
                'reports.view', 'activity.view', 'team.view',
            ],
            self::Member => [
                'clients.view', 'projects.view',
                'tasks.view', 'tasks.manage',
                'time.track', 'expenses.view', 'expenses.manage',
                'files.view', 'files.manage',
                'activity.view', 'team.view',
            ],
            self::Client => [],
        };
    }
}
