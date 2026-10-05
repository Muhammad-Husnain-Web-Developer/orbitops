<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/*
 * Workspace activity, board moves and timeline updates. Team members only:
 * client portal users never receive internal realtime events.
 */
Broadcast::channel('workspace.{workspaceId}', function (User $user, int $workspaceId) {
    $membership = $user->memberships()->where('workspace_id', $workspaceId)->where('status', 'active')->first();

    return $membership !== null && ! $membership->isClient();
});

/*
 * Invoice and expense activity carries amounts, so each has its own channel
 * gated by the matching permission in that workspace.
 */
Broadcast::channel('workspace.{workspaceId}.{area}', function (User $user, int $workspaceId, string $area) {
    if (! in_array($area, ['invoices', 'expenses'], true)) {
        return false;
    }

    $membership = $user->memberships()->where('workspace_id', $workspaceId)->where('status', 'active')->first();

    if ($membership === null || $membership->isClient()) {
        return false;
    }

    // Permissions are team-scoped; evaluate them for the workspace being joined.
    $previous = getPermissionsTeamId();
    setPermissionsTeamId($workspaceId);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    try {
        return $user->can("{$area}.view");
    } finally {
        setPermissionsTeamId($previous);
        $user->unsetRelation('roles')->unsetRelation('permissions');
    }
});
