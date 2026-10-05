<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Model;

trait ChecksWorkspace
{
    /**
     * Defence in depth on top of the tenant scope: the record must live in the
     * active workspace and the user must be an active member of it.
     */
    protected function inWorkspace(User $user, Model $model): bool
    {
        $workspaceId = app(CurrentWorkspace::class)->id();

        return $workspaceId !== null
            && (int) $model->getAttribute('workspace_id') === $workspaceId
            && $user->belongsToWorkspace($workspaceId);
    }

    protected function allowed(User $user, string $permission, ?Model $model = null): bool
    {
        return $user->can($permission) && ($model === null || $this->inWorkspace($user, $model));
    }
}
