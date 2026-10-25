<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160', Rule::unique('products', 'name')],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'rating' => ['required', 'numeric', 'between:0,5', 'decimal:0,1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.unique' => 'A product with this name already exists.'];
    }

    /** @return array<string, array{description: string, example?: string|int|float}> */
    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Trimmed, case-insensitively unique name (up to 160 characters).', 'example' => 'Studio Wireless Headphones'],
            'description' => ['description' => 'Description, or null; required as a key for PUT.', 'example' => 'Comfortable wireless headphones for everyday listening.'],
            'category_id' => ['description' => 'ID from the categories endpoint.', 'example' => 1],
            'price' => ['description' => 'Nonnegative decimal, at most two places, up to 9999999999.99. Responses return a decimal string.', 'example' => '129.00'],
            'rating' => ['description' => 'Rating between 0 and 5, at most one decimal place.', 'example' => 4.8],
        ];
    }
}
