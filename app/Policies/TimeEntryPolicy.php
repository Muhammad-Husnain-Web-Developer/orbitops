<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class TimeEntryPolicy
{
    use ChecksWorkspace;

    public function create(User $user): bool
    {
        return $user->can('time.track');
    }

    /**
     * People manage their own entries; managers can correct anyone's.
     */
    public function update(User $user, TimeEntry $entry): bool
    {
        return $this->inWorkspace($user, $entry)
            && (($entry->user_id === $user->id && $user->can('time.track')) || $user->can('time.view_all'));
    }

    public function delete(User $user, TimeEntry $entry): bool
    {
        return $this->update($user, $entry);
    }
}
