<?php

namespace App\Contracts;

use App\Exceptions\CategoryInUse;
use App\Exceptions\DuplicateCategoryName;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface CategoryRepository
{
    /** @return Collection<int, Category> */
    public function all(): Collection;

    /** @throws ModelNotFoundException */
    public function find(int $id): Category;

    /** Must be called inside a transaction. Missing records throw ModelNotFoundException. */
    public function lock(int $id): Category;

    /** @throws DuplicateCategoryName */
    public function create(string $name): Category;

    /** @throws DuplicateCategoryName */
    public function update(Category $category, string $name): Category;

    public function hasProducts(Category $category): bool;

    /** @throws CategoryInUse */
    public function delete(Category $category): void;
}
