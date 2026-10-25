<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:160'],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')],
            'min_price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2', Rule::when($this->filled('min_price'), 'gte:min_price')],
            'sort_by' => ['sometimes', Rule::in(['price', 'rating'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array<string, array{description: string, example?: string|int|float}> */
    public function queryParameters(): array
    {
        return [
            'search' => ['description' => 'Case-insensitive literal name substring.', 'example' => 'studio'],
            'category_id' => ['description' => 'ID from the categories endpoint.', 'example' => 1],
            'min_price' => ['description' => 'Inclusive minimum price (nonnegative, up to two decimal places).', 'example' => '25.00'],
            'max_price' => ['description' => 'Inclusive maximum price; must be greater than or equal to min_price.', 'example' => '200.00'],
            'sort_by' => ['description' => 'Sort by price or rating. Omit for newest first.', 'example' => 'price'],
            'sort_direction' => ['description' => 'Ascending or descending, defaults to desc. ID breaks ties.', 'example' => 'asc'],
            'page' => ['description' => 'Page number, starting at 1.', 'example' => 1],
            'per_page' => ['description' => 'Items per page, 1–100; defaults to 12.', 'example' => 12],
        ];
    }
}
