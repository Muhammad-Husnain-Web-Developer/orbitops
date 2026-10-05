<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;

class WorkspacePolicy
{
    public function update(User $user, Workspace $workspace): bool
    {
        return $this->isCurrent($workspace) && $user->can('workspace.settings');
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $this->isCurrent($workspace) && $user->can('team.manage');
    }

    public function manageRoles(User $user, Workspace $workspace): bool
    {
        return $this->isCurrent($workspace) && $user->can('workspace.roles');
    }

    public function manageBilling(User $user, Workspace $workspace): bool
    {
        return $this->isCurrent($workspace) && $user->can('workspace.billing');
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->isCurrent($workspace)
            && $workspace->owner_id === $user->id
            && $user->can('workspace.delete');
    }

    protected function isCurrent(Workspace $workspace): bool
    {
        return app(CurrentWorkspace::class)->id() === $workspace->id;
    }
}
