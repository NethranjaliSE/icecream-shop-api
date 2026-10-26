<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query string for GET /api/items.
 */
class ListItemsRequest extends FormRequest
{
    public const SORTABLE = ['name', 'sku', 'price', 'quantity', 'created_at'];

    public function authorize(): bool
    {
        return true; // Checked by the controller's `can` middleware.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sorts = collect(self::SORTABLE)->flatMap(fn ($f) => [$f, "-{$f}"])->all();

        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'low_stock' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', Rule::in($sorts)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
