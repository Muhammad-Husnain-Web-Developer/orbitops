<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attachment */
class AttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'kind' => $this->kind(),
            'size' => $this->size,
            'visible_to_client' => $this->visible_to_client,
            'created_at' => $this->created_at?->toIso8601String(),
            'download_url' => route('files.download', $this->id),
            'project' => $this->whenLoaded('project', fn () => $this->project ? ['id' => $this->project->id, 'name' => $this->project->name, 'color' => $this->project->color] : null),
            'client' => $this->whenLoaded('client', fn () => $this->client ? ['id' => $this->client->id, 'name' => $this->client->name] : null),
            'uploader' => UserResource::make($this->whenLoaded('uploader')),
        ];
    }
}
