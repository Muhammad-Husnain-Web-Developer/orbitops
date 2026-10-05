<?php

namespace App\Models\Scopes;

use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WorkspaceScope implements Scope
{
    /**
     * Constrain tenant-owned models to the active workspace.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $workspaceId = app(CurrentWorkspace::class)->id();

        if ($workspaceId !== null) {
            $builder->where($model->qualifyColumn('workspace_id'), $workspaceId);
        }
    }
}
