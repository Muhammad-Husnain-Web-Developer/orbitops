<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    use ValidatesTenantReferences;

    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(['milestone_id', 'assignee_id', 'due_date', 'estimate_hours'])
            ->filter(fn ($key) => $this->has($key) && $this->input($key) === '')
            ->mapWithKeys(fn ($key) => [$key => null])
            ->all());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:190'],
            'project_id' => ['required', $this->inWorkspace('projects')],
            'milestone_id' => ['nullable', Rule::exists('milestones', 'id')->where('project_id', $this->input('project_id'))],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'assignee_id' => ['nullable', $this->teamMember()],
            'due_date' => ['nullable', 'date'],
            'estimate_hours' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'visible_to_client' => ['boolean'],
        ];
    }
}
