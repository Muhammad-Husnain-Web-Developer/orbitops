<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Partial updates: the task drawer saves one field at a time.
 */
class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $projectId = $this->input('project_id', $this->route('task')->project_id);

        return [
            'title' => ['sometimes', 'required', 'string', 'max:190'],
            'project_id' => ['sometimes', 'required', $this->inWorkspace('projects')],
            'milestone_id' => ['sometimes', 'nullable', Rule::exists('milestones', 'id')->where('project_id', $projectId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'status' => ['sometimes', 'required', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(TaskPriority::class)],
            'assignee_id' => ['sometimes', 'nullable', $this->teamMember()],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'estimate_hours' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],
            'visible_to_client' => ['sometimes', 'boolean'],
        ];
    }
}
