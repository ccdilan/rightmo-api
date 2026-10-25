<?php

namespace App\Repositories;

use App\Contracts\CategoryRepository;
use App\Exceptions\CategoryInUse;
use App\Exceptions\DuplicateCategoryName;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentCategoryRepository implements CategoryRepository
{
    /** @return Collection<int, Category> */
    public function all(): Collection
    {
        return Category::query()->withCount('products')->orderBy('name')->orderBy('id')->get();
    }

    public function find(int $id): Category
    {
        return Category::query()->withCount('products')->findOrFail($id);
    }

    public function lock(int $id): Category
    {
        return Category::query()->lockForUpdate()->findOrFail($id);
    }

    public function create(string $name): Category
    {
        try {
            return Category::query()->create(['name' => $name])->loadCount('products');
        } catch (UniqueConstraintViolationException $exception) {
            throw new DuplicateCategoryName(previous: $exception);
        }
    }

    public function update(Category $category, string $name): Category
    {
        try {
            $category->update(['name' => $name]);

            return $category->loadCount('products');
        } catch (UniqueConstraintViolationException $exception) {
            throw new DuplicateCategoryName(previous: $exception);
        }
    }

    public function hasProducts(Category $category): bool
    {
        return $category->products()->exists();
    }

    public function delete(Category $category): void
    {
        try {
            Category::query()->whereKey($category->id)->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1451) {
                throw new CategoryInUse(previous: $exception);
            }
            throw $exception;
        }
    }
}
