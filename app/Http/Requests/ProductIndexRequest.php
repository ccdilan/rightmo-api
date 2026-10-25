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
}
