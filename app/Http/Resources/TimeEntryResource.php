<?php

namespace App\Http\Resources;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeEntry */
class TimeEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $this->isRunning() ? (int) $this->started_at->diffInSeconds(now()) : $this->duration_seconds,
            'is_running' => $this->isRunning(),
            'billable' => $this->billable,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'project' => $this->whenLoaded('project', fn () => ['id' => $this->project->id, 'name' => $this->project->name, 'color' => $this->project->color]),
            'task' => $this->whenLoaded('task', fn () => $this->task ? ['id' => $this->task->id, 'title' => $this->task->title] : null),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
