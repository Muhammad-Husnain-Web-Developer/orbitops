<?php

namespace App\Http\Requests;

use App\Enums\ExpenseCategory;
use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ExpenseRequest extends FormRequest
{
    use ValidatesTenantReferences;

    public function authorize(): bool
    {
        $expense = $this->route('expense');

        return $expense ? $this->user()->can('update', $expense) : $this->user()->can('create', Expense::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['currency' => strtoupper((string) $this->input('currency', 'USD'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:190'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'currency' => ['required', 'string', 'size:3'],
            'spent_on' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'project_id' => ['nullable', $this->inWorkspace('projects')],
            'billable' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf', 'heic'])->max(10 * 1024)],
            'remove_receipt' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'spent_on.before_or_equal' => 'Expenses can only be logged for today or earlier.',
            'receipt.max' => 'Receipts can be up to 10 MB.',
        ];
    }
}
