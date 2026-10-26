<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT replaces the record (name is required); PATCH updates only the
 * fields sent.
 */
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Checked by the controller's `can` middleware.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name' => [$this->isMethod('PUT') ? 'required' : 'sometimes', 'string', 'max:100'],
            'email' => [
                'sometimes', 'nullable', 'string', 'email', 'max:255',
                Rule::unique('customers', 'email')->ignore($customer),
            ],
            'phone' => [
                'sometimes', 'nullable', 'string', 'max:20', 'regex:'.StoreCustomerRequest::PHONE_REGEX,
                Rule::unique('customers', 'phone')->ignore($customer),
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
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
