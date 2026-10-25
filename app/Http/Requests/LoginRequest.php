<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']];
    }

    /** @return array<string, array{description: string, example?: string|int|float}> */
    public function bodyParameters(): array
    {
        return [
            'email' => ['description' => 'Email address of an existing account.', 'example' => 'demo@rightmo.test'],
            'password' => ['description' => 'Account password. Local demo only; explicitly seed DemoSeeder first.', 'example' => 'RightmoDemo!2026'],
        ];
    }
}
