<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationFeed
{
    /**
     * A user's notifications for one workspace, plus workspace-less ones (e.g. invitations).
     *
     * @return Builder<DatabaseNotification>
     */
    public static function query(User $user, ?int $workspaceId): Builder
    {
        return $user->notifications()
            ->where(fn ($query) => $query
                ->whereNull('data->workspace_id')
                ->orWhere('data->workspace_id', $workspaceId))
            ->getQuery();
    }
}
