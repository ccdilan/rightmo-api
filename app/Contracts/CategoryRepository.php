<?php

namespace App\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepository
{
    /** @return Collection<int, Category> */
    public function all(): Collection;
}
