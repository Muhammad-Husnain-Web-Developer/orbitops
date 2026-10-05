<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:'.$this->route('invoice')->balance()],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::in(array_keys(self::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
        ];
    }

    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'card' => 'Card',
        'cash' => 'Cash',
        'check' => 'Check',
        'other' => 'Other',
    ];

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'The payment cannot be more than the balance due.',
        ];
    }
}
