<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->relationLoaded('project') ? $this->key() : null,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'due_date' => $this->due_date?->toDateString(),
            'position' => $this->position,
            'estimate_minutes' => $this->estimate_minutes,
            'visible_to_client' => $this->visible_to_client,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'project_id' => $this->project_id,
            'milestone_id' => $this->milestone_id,
            'assignee_id' => $this->assignee_id,
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),
            'tracked_seconds' => $this->whenHas('tracked_seconds', fn () => (int) $this->tracked_seconds),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'name' => $this->project->name,
                'code' => $this->project->code,
                'color' => $this->project->color,
            ]),
            'milestone' => $this->whenLoaded('milestone', fn () => $this->milestone ? ['id' => $this->milestone->id, 'name' => $this->milestone->name] : null),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
