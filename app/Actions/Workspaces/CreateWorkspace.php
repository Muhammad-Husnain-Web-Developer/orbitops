<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateWorkspace
{
    public function __construct(
        protected AddWorkspaceMember $addMember,
        protected CurrentWorkspace $current,
    ) {}

    /**
     * Create a workspace, give it its own copy of the default roles and make the user its owner.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $owner, array $attributes): Workspace
    {
        return DB::transaction(function () use ($owner, $attributes) {
            $workspace = Workspace::create([
                ...$attributes,
                'owner_id' => $owner->id,
            ]);

            $this->current->runAs($workspace, function () use ($workspace, $owner) {
                $this->provisionRoles($workspace);
                $this->addMember->handle($workspace, $owner, WorkspaceRole::Owner);
            });

            $owner->switchWorkspace($workspace);

            return $workspace;
        });
    }

    /**
     * Roles are scoped per workspace so each tenant can tune its own permission matrix.
     */
    public function provisionRoles(Workspace $workspace): void
    {
        foreach (PermissionCatalog::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (WorkspaceRole::cases() as $case) {
            $role = Role::firstOrCreate([
                'name' => $case->value,
                'guard_name' => 'web',
                'workspace_id' => $workspace->id,
            ]);

            $role->syncPermissions($case->defaultPermissions());
        }
    }
}
