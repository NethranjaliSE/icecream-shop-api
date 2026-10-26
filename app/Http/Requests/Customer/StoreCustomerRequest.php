<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Digits, spaces, dashes, brackets and an optional leading "+".
     */
    public const PHONE_REGEX = '/^\+?[0-9\s\-()]{7,20}$/';

    public function authorize(): bool
    {
        return true; // Checked by the controller's `can` middleware.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:'.self::PHONE_REGEX, 'unique:customers,phone'],
            'address' => ['nullable', 'string', 'max:255'],
            'loyalty_points' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
