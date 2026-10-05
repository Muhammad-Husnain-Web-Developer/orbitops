<?php

namespace App\Http\Resources;

use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Milestone */
class MilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'position' => $this->position,
            'requires_approval' => $this->requires_approval,
            'approval_status' => $this->approval_status,
            'approval_note' => $this->approval_note,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'tasks_count' => $this->whenCounted('tasks'),
            'completed_tasks_count' => $this->whenCounted('completed_tasks'),
            'approver' => UserResource::make($this->whenLoaded('approver')),
            'project' => ProjectResource::make($this->whenLoaded('project')),
        ];
    }
}
