<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
        return ['name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.unique' => 'A category with this name already exists.'];
    }

    /** @return array<string, array{description: string, example: string}> */
    public function bodyParameters(): array
    {
        return ['name' => ['description' => 'Trimmed, case-insensitively unique name, up to 120 characters. Required for POST/PUT; omitted PATCH names are preserved.', 'example' => 'Home office']];
    }
}
