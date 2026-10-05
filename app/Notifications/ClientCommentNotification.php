<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Project;
use Illuminate\Support\Str;

class ClientCommentNotification extends WorkspaceNotification
{
    public function __construct(public Comment $comment, public Project $project) {}

    public function type(): string
    {
        return 'client_comment';
    }

    protected function content(object $notifiable): array
    {
        $this->comment->loadMissing('author');

        return [
            'title' => $this->comment->author->name.' sent a message on '.$this->project->name,
            'body' => Str::limit($this->comment->body, 140),
            'url' => route('projects.show', ['project' => $this->project, 'tab' => 'messages']),
            'actor' => $this->comment->author,
            'workspace_id' => $this->project->workspace_id,
        ];
    }
}
