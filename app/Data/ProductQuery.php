<?php

namespace App\Data;

final readonly class ProductQuery
{
    /** @param list<int> $categoryIds */
    public function __construct(
        public ?string $search = null,
        public ?int $categoryId = null,
        public ?string $minPrice = null,
        public ?string $maxPrice = null,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
        public int $page = 1,
        public int $perPage = 12,
        public array $categoryIds = [],
        public ?string $minRating = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            $input['search'] ?? null,
            isset($input['category_id']) ? (int) $input['category_id'] : null,
            isset($input['min_price']) ? (string) $input['min_price'] : null,
            isset($input['max_price']) ? (string) $input['max_price'] : null,
            $input['sort_by'] ?? 'created_at', $input['sort_direction'] ?? 'desc',
            (int) ($input['page'] ?? 1), (int) ($input['per_page'] ?? 12),
            array_values(array_map(intval(...), $input['category_ids'] ?? [])),
            isset($input['min_rating']) ? (string) $input['min_rating'] : null,
        );
    }
}
