<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class TaskPolicy
{
    use ChecksWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $this->allowed($user, 'tasks.view', $task);
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.manage');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->allowed($user, 'tasks.manage', $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->allowed($user, 'tasks.delete', $task);
    }

    /**
     * Anyone who can work on tasks can comment and log progress on them.
     */
    public function comment(User $user, Task $task): bool
    {
        return $this->allowed($user, 'tasks.view', $task);
    }
}
