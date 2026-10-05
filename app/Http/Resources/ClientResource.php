<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials,
            'industry' => $this->industry,
            'status' => $this->status?->value,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'currency' => $this->currency,
            'projects_count' => $this->whenCounted('projects'),
            'active_projects_count' => $this->whenCounted('active_projects'),
            'outstanding' => $this->whenHas('outstanding', fn () => round((float) $this->outstanding, 2)),
            'lifetime_value' => $this->whenHas('lifetime_value', fn () => round((float) $this->lifetime_value, 2)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
