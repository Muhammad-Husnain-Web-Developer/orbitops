<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\TimeEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class TimeEntryRequest extends FormRequest
{
    use ValidatesTenantReferences;

    public function authorize(): bool
    {
        $entry = $this->route('timeEntry');

        return $entry ? $this->user()->can('update', $entry) : $this->user()->can('create', TimeEntry::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', $this->inWorkspace('projects')],
            'task_id' => ['nullable', $this->inWorkspace('tasks')],
            'description' => ['nullable', 'string', 'max:190'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'billable' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $validator->errors()->has('start') && ! $validator->errors()->has('end') && $this->input('end') <= $this->input('start')) {
                    $validator->errors()->add('end', 'End time must be after the start time.');
                }
            },
        ];
    }

    public function startedAt(): Carbon
    {
        return Carbon::parse("{$this->input('date')} {$this->input('start')}");
    }

    public function endedAt(): Carbon
    {
        return Carbon::parse("{$this->input('date')} {$this->input('end')}");
    }
}
