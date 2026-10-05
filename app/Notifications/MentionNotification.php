<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Str;

class MentionNotification extends WorkspaceNotification
{
    public function __construct(public Comment $comment) {}

    public function type(): string
    {
        return 'mention';
    }

    protected function content(object $notifiable): array
    {
        $this->comment->loadMissing('author', 'commentable');
        $subject = $this->comment->commentable;

        $url = match (true) {
            $subject instanceof Task => route('tasks.index', ['task' => $subject->id]),
            $subject instanceof Project => route('projects.show', ['project' => $subject, 'tab' => 'messages']),
            default => route('dashboard'),
        };

        return [
            'title' => $this->comment->author->firstName().' mentioned you',
            'body' => Str::limit($this->comment->body, 140),
            'url' => $url,
            'actor' => $this->comment->author,
            'workspace_id' => $this->comment->workspace_id,
        ];
    }
}
