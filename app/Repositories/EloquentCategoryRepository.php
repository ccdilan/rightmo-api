<?php

namespace App\Repositories;

use App\Contracts\CategoryRepository;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository implements CategoryRepository
{
    /** @return Collection<int, Category> */
    public function all(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }
}
