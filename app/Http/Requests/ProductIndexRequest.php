<?php

namespace App\Http\Requests;

use Closure;
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
            'search' => ['bail', 'sometimes', 'nullable', 'string', 'max:160', function (string $attribute, mixed $value, Closure $fail): void {
                if (count(preg_split('/\s+/u', trim($value), flags: PREG_SPLIT_NO_EMPTY) ?: []) > 8) {
                    $fail('Search may contain at most 8 words.');
                }
            }],
            'category_id' => ['sometimes', 'prohibits:category_ids', 'integer', Rule::exists('categories', 'id')],
            'category_ids' => ['bail', 'sometimes', 'array', 'list', 'min:1', 'max:20', Rule::exists('categories', 'id')],
            'category_ids.*' => ['bail', 'integer', 'min:1', 'distinct'],
            'min_rating' => ['sometimes', 'numeric', 'between:0,5', 'decimal:0,1'],
            'min_price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'max_price' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2', Rule::when($this->filled('min_price'), 'gte:min_price')],
            'sort_by' => ['sometimes', Rule::in(['price', 'rating'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array<string, array{description: string, example?: string|int|float|list<int>}> */
    public function queryParameters(): array
    {
        return [
            'search' => ['description' => 'All words must occur in the name, in any order (case insensitive). Up to 8 words; wildcard characters are literal.', 'example' => 'studio'],
            'category_id' => ['description' => 'Legacy single category ID. Do not combine with category_ids.', 'example' => 1],
            'category_ids' => ['description' => 'Match any selected category, up to 20 distinct IDs. Send category_ids[]=1&category_ids[]=2. Do not combine with category_id.', 'example' => [1, 2]],
            'min_rating' => ['description' => 'Inclusive minimum rating from 0 to 5, up to one decimal place.', 'example' => 4],
            'min_price' => ['description' => 'Inclusive minimum price (nonnegative, up to two decimal places).', 'example' => '25.00'],
            'max_price' => ['description' => 'Inclusive maximum price; must be greater than or equal to min_price.', 'example' => '200.00'],
            'sort_by' => ['description' => 'Sort by price or rating. Omit for newest first.', 'example' => 'price'],
            'sort_direction' => ['description' => 'Ascending or descending, defaults to desc. ID breaks ties.', 'example' => 'asc'],
            'page' => ['description' => 'Page number, starting at 1.', 'example' => 1],
            'per_page' => ['description' => 'Items per page, 1–100; defaults to 12.', 'example' => 12],
        ];
    }
}
