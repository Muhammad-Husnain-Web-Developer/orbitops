<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority,
            'color' => $this->color,
            'billing_type' => $this->billing_type,
            'budget' => (float) $this->budget,
            'hourly_rate' => $this->hourly_rate !== null ? (float) $this->hourly_rate : null,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'progress' => $this->when(isset($this->tasks_count), fn () => $this->progress()),
            'tasks_count' => $this->whenCounted('tasks'),
            'completed_tasks_count' => $this->whenCounted('completed_tasks'),
            'tracked_seconds' => $this->whenHas('tracked_seconds', fn () => (int) $this->tracked_seconds),
            'client' => ClientResource::make($this->whenLoaded('client')),
            'owner' => UserResource::make($this->whenLoaded('owner')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'client_id' => $this->client_id,
            'owner_id' => $this->owner_id,
        ];
    }
}
