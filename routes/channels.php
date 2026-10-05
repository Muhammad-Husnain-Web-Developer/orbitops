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
