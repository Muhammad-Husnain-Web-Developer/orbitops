<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;

class TaskCompletedNotification extends WorkspaceNotification
{
    public function __construct(public Task $task, public ?User $completedBy = null) {}

    public function type(): string
    {
        return 'task_completed';
    }

    protected function content(object $notifiable): array
    {
        $this->task->loadMissing('project');

        return [
            'title' => ($this->completedBy?->firstName() ?? 'Someone').' completed a task',
            'body' => "{$this->task->title} · {$this->task->project->name}",
            'url' => route('tasks.index', ['task' => $this->task->id]),
            'actor' => $this->completedBy,
            'workspace_id' => $this->task->workspace_id,
        ];
    }
}
