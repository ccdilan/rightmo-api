<?php

namespace App\Services;

use App\Contracts\CategoryRepository;
use App\Exceptions\CategoryInUse;
use App\Models\Category;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;

final class CategoryService
{
    public function __construct(private CategoryRepository $categories, private DatabaseManager $database) {}

    /** @return Collection<int, Category> */
    public function list(): Collection
    {
        return $this->categories->all();
    }

    public function find(int $id): Category
    {
        return $this->categories->find($id);
    }

    public function create(string $name): Category
    {
        return $this->database->transaction(fn () => $this->categories->create($name));
    }

    public function update(int $id, ?string $name): Category
    {
        return $this->database->transaction(function () use ($id, $name): Category {
            $category = $this->categories->lock($id);

            return $name === null ? $this->categories->find($id) : $this->categories->update($category, $name);
        });
    }

    public function delete(int $id): void
    {
        $this->database->transaction(function () use ($id): void {
            $category = $this->categories->lock($id);
            if ($this->categories->hasProducts($category)) {
                throw new CategoryInUse;
            }
            $this->categories->delete($category);
        });
    }
}
