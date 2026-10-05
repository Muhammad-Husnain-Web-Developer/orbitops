<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class ProjectPolicy
{
    use ChecksWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->allowed($user, 'projects.view', $project);
    }

    public function create(User $user): bool
    {
        return $user->can('projects.manage');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allowed($user, 'projects.manage', $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allowed($user, 'projects.delete', $project);
    }
}
