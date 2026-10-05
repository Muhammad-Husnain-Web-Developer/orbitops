<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category?->value,
            'vendor' => $this->vendor,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'spent_on' => $this->spent_on?->toDateString(),
            'status' => $this->status?->value,
            'billable' => $this->billable,
            'notes' => $this->notes,
            'project_id' => $this->project_id,
            'receipt_name' => $this->receipt_name,
            'receipt_url' => $this->receipt_path ? route('expenses.receipt', $this->id) : null,
            'project' => $this->whenLoaded('project', fn () => $this->project ? ['id' => $this->project->id, 'name' => $this->project->name, 'color' => $this->project->color] : null),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
