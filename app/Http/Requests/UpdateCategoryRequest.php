<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore((int) $this->route('category'))];

        return ['name' => $this->isMethod('PATCH') ? ['sometimes', ...$rules] : $rules];
    }
}
