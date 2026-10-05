<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;

class TaskAssignedNotification extends WorkspaceNotification
{
    public function __construct(public Task $task, public ?User $assignedBy = null) {}

    public function type(): string
    {
        return 'task_assigned';
    }

    protected function content(object $notifiable): array
    {
        $this->task->loadMissing('project');

        return [
            'title' => ($this->assignedBy?->firstName() ?? 'Someone').' assigned you a task',
            'body' => "{$this->task->title} · {$this->task->project->name}",
            'url' => route('tasks.index', ['task' => $this->task->id]),
            'actor' => $this->assignedBy,
            'workspace_id' => $this->task->workspace_id,
        ];
    }
}
