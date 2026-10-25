<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=10000,max_height=10000']];
    }

    /** @return array<string, array{description: string, example?: string|int|float}> */
    public function bodyParameters(): array
    {
        return [
            'image' => ['description' => 'JPEG, PNG, or WebP file up to 5 MiB. Actual image content is validated; SVG is rejected.', 'example' => 'docs/examples/product.png'],
        ];
    }
}
