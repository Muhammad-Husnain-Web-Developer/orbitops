<?php

namespace App\Models\Concerns;

use App\Models\Scopes\WorkspaceScope;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-owned models: automatically scoped to, and stamped with, the active workspace.
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope(new WorkspaceScope);

        static::creating(function ($model) {
            if (! $model->workspace_id && $workspaceId = app(CurrentWorkspace::class)->id()) {
                $model->workspace_id = $workspaceId;
            }
        });
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
