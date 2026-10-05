<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    use ValidatesTenantReferences;

    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('code'))) : null,
            'client_id' => $this->input('client_id') ?: null,
            'budget' => $this->input('budget') === '' ? 0 : $this->input('budget'),
            'hourly_rate' => $this->input('hourly_rate') === '' ? null : $this->input('hourly_rate'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:10'],
            'client_id' => ['nullable', $this->inWorkspace('clients')],
            'owner_id' => ['nullable', $this->teamMember()],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'color' => ['required', 'in:violet,blue,cyan,emerald,amber,rose'],
            'billing_type' => ['required', 'in:fixed,hourly,retainer'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:100000', 'required_if:billing_type,hourly'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'member_ids' => ['array'],
            'member_ids.*' => ['integer', $this->teamMember()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'The due date must be on or after the start date.',
            'hourly_rate.required_if' => 'Set an hourly rate for hourly projects.',
        ];
    }
}
