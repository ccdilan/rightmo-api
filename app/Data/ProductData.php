<?php

namespace App\Data;

final readonly class ProductData
{
    /** @param array<string, int|string|null> $attributes */
    public function __construct(public array $attributes) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $attributes = [];
        foreach (['name', 'description', 'category_id', 'price', 'rating'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $attributes[$key] = $key === 'category_id' ? (int) $input[$key] : ($input[$key] === null ? null : (string) $input[$key]);
        }

        return new self($attributes);
    }
}
