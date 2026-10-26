<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT replaces the item, so the same fields as create are required.
 * PATCH is a partial update: only the fields sent are validated and saved.
 */
class UpdateItemRequest extends FormRequest
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
        $required = $this->isMethod('PUT') ? 'required' : 'sometimes';

        return [
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'name' => [$required, 'string', 'max:100'],
            'sku' => [
                $required, 'string', 'max:32', 'alpha_dash:ascii',
                Rule::unique('items', 'sku')->ignore($this->route('item')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => [$required, 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'quantity' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'reorder_level' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('sku'))) {
            $this->merge(['sku' => strtoupper(trim($this->input('sku')))]);
        }
    }
}
