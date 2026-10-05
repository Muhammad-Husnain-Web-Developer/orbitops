<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Models\Project;
use App\Notifications\ClientCommentNotification;
use Illuminate\Support\Facades\Notification;

class NotifyProjectTeamOfClientMessage
{
    /**
     * When a client writes on a project thread, let the project team know.
     */
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment->loadMissing('commentable', 'author');
        $project = $comment->commentable;

        if (! $project instanceof Project || ! $comment->author?->membershipFor($comment->workspace_id)?->isClient()) {
            return;
        }

        $recipients = $project->members()->get()->push($project->owner)->filter()->unique('id');

        Notification::send($recipients, new ClientCommentNotification($comment, $project));
    }
}
