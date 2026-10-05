<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class MilestonePolicy
{
    use ChecksWorkspace;

    public function create(User $user): bool
    {
        return $user->can('projects.manage');
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $this->allowed($user, 'projects.manage', $milestone);
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $this->allowed($user, 'projects.manage', $milestone);
    }
}
