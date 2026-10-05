<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    use ValidatesTenantReferences;

    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        return $invoice ? $this->user()->can('update', $invoice) : $this->user()->can('create', Invoice::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'USD')),
            'items' => collect($this->input('items', []))
                ->filter(fn ($item) => is_array($item) && (filled($item['description'] ?? null) || (float) ($item['unit_price'] ?? 0) > 0))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', $this->inWorkspace('clients')],
            'project_id' => ['nullable', $this->inWorkspace('projects')->where('client_id', $this->integer('client_id'))],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'size:3'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', $this->input('discount_type') === 'percent' ? 'max:100' : 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'send' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Choose a client to bill.',
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
            'items.*.description.required' => 'Describe this line item.',
            'items.*.quantity.gt' => 'Quantity must be more than zero.',
            'project_id.exists' => 'Choose a project that belongs to this client.',
            'due_date.after_or_equal' => 'The due date cannot be before the issue date.',
        ];
    }
}
