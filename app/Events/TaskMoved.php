<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lets open Kanban boards refresh when a teammate moves a card.
 */
class TaskMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Task $task, public ?int $actorId = null) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('workspace.'.$this->task->workspace_id)];
    }

    public function broadcastAs(): string
    {
        return 'task.moved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'status' => $this->task->status->value,
            'position' => $this->task->position,
            'actor_id' => $this->actorId,
        ];
    }
}
