<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $milestone = $this->route('milestone');

        return $milestone ? $this->user()->can('update', $milestone) : $this->user()->can('update', $this->route('project'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->route('milestone') ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'in:pending,in_progress,completed'],
            'requires_approval' => ['sometimes', 'boolean'],
        ];
    }
}
