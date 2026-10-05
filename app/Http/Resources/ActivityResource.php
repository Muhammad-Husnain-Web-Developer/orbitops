<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'label' => $this->properties['label'] ?? null,
            'properties' => $this->properties ?? [],
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'project_id' => $this->project_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'causer' => UserResource::make($this->whenLoaded('causer')),
        ];
    }
}
