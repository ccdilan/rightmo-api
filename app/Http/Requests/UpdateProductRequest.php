<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['required', 'string', 'max:160', Rule::unique('products', 'name')->ignore((int) $this->route('product'))];
        if ($this->isMethod('PATCH')) {
            foreach ($rules as $key => $value) {
                $rules[$key] = ['sometimes', ...$value];
            }
        } else {
            $rules['description'] = ['present', 'nullable', 'string', 'max:10000'];
        }

        return $rules;
    }
}
