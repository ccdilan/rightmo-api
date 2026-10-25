<?php

namespace App\Repositories;

use App\Contracts\ProductRepository;
use App\Data\ProductData;
use App\Data\ProductQuery;
use App\Exceptions\DuplicateProductName;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentProductRepository implements ProductRepository
{
    /** @return LengthAwarePaginator<int, Product> */
    public function paginate(ProductQuery $query): LengthAwarePaginator
    {
        $builder = Product::query()->with('category');
        foreach (array_unique(preg_split('/\s+/u', trim($query->search ?? ''), flags: PREG_SPLIT_NO_EMPTY) ?: []) as $term) {
            $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
            $builder->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$search.'%']);
        }
        if ($query->categoryId !== null) {
            $builder->where('category_id', $query->categoryId);
        }
        if ($query->categoryIds !== []) {
            $builder->whereIn('category_id', $query->categoryIds);
        }
        if ($query->minRating !== null) {
            $builder->where('rating', '>=', $query->minRating);
        }
        if ($query->minPrice !== null) {
            $builder->where('price', '>=', $query->minPrice);
        }
        if ($query->maxPrice !== null) {
            $builder->where('price', '<=', $query->maxPrice);
        }
        $sort = in_array($query->sortBy, ['price', 'rating', 'created_at'], true) ? $query->sortBy : 'created_at';
        $direction = $query->sortDirection === 'asc' ? 'asc' : 'desc';

        return $builder->orderBy($sort, $direction)->orderBy('id', $direction)
            ->paginate($query->perPage, ['*'], 'page', $query->page)->withQueryString();
    }

    public function find(int $id): Product
    {
        return Product::query()->with('category')->findOrFail($id);
    }

    public function lock(int $id): Product
    {
        return Product::query()->lockForUpdate()->findOrFail($id);
    }

    public function create(ProductData $data): Product
    {
        try {
            return Product::query()->create($data->attributes)->load('category');
        } catch (UniqueConstraintViolationException $e) {
            throw new DuplicateProductName(previous: $e);
        }
    }

    public function update(Product $product, ProductData $data): Product
    {
        try {
            $product->update($data->attributes);

            return $product->load('category');
        } catch (UniqueConstraintViolationException $e) {
            throw new DuplicateProductName(previous: $e);
        }
    }

    public function setImage(Product $product, string $path): Product
    {
        $product->update(['image_path' => $path]);

        return $product->load('category');
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
