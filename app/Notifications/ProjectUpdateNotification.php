<?php

namespace App\Notifications;

use App\Models\Milestone;
use App\Models\User;

class ProjectUpdateNotification extends WorkspaceNotification
{
    public function __construct(public Milestone $milestone, public ?User $actor = null) {}

    public function type(): string
    {
        return 'project_update';
    }

    protected function content(object $notifiable): array
    {
        $this->milestone->loadMissing('project');

        $approved = $this->milestone->approval_status === 'approved';

        return [
            'title' => $approved
                ? "Milestone approved: {$this->milestone->name}"
                : "Changes requested on {$this->milestone->name}",
            'body' => $this->milestone->approval_note ?: $this->milestone->project->name,
            'url' => route('projects.show', ['project' => $this->milestone->project_id, 'tab' => 'milestones']),
            'actor' => $this->actor,
            'workspace_id' => $this->milestone->workspace_id,
        ];
    }
}
