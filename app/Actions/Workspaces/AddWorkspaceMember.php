<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;

class AddWorkspaceMember
{
    public function __construct(protected CurrentWorkspace $current) {}

    /**
     * Attach a user to a workspace with a role. Client-role members are linked to a client record.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Workspace $workspace, User $user, WorkspaceRole $role, ?int $clientId = null, array $attributes = []): Membership
    {
        $membership = Membership::updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $user->id],
            [
                'client_id' => $role === WorkspaceRole::Client ? $clientId : null,
                'status' => 'active',
                'joined_at' => now(),
                ...$attributes,
            ],
        );

        $this->current->runAs($workspace, function () use ($user, $role) {
            $user->unsetRelation('roles');
            $user->syncRoles([$role->value]);
        });

        if (! $user->current_workspace_id) {
            $user->switchWorkspace($workspace);
        }

        return $membership;
    }
}
