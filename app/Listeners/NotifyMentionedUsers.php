<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Notifications\MentionNotification;

class NotifyMentionedUsers
{
    /**
     * Notify teammates referenced as @firstname in a comment.
     */
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;

        if (! str_contains($comment->body, '@')) {
            return;
        }

        preg_match_all('/@([\pL][\pL\-]*)/u', $comment->body, $matches);
        $handles = collect($matches[1])->map(fn ($handle) => mb_strtolower($handle))->unique();

        $comment->workspace->teamMembers()->get()
            ->filter(fn ($user) => $handles->contains(mb_strtolower($user->firstName())))
            ->reject(fn ($user) => $user->id === $comment->user_id)
            ->each(fn ($user) => $user->notify(new MentionNotification($comment)));
    }
}
