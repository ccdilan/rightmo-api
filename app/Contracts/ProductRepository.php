<?php

namespace App\Contracts;

use App\Data\ProductData;
use App\Data\ProductQuery;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepository
{
    /** @return LengthAwarePaginator<int, Product> */
    public function paginate(ProductQuery $query): LengthAwarePaginator;

    /** @throws ModelNotFoundException<Product> */
    public function find(int $id): Product;

    /** Must be called inside a transaction. @throws \Illuminate\Database\Eloquent\ModelNotFoundException<Product> */
    public function lock(int $id): Product;

    public function create(ProductData $data): Product;

    public function update(Product $product, ProductData $data): Product;

    public function setImage(Product $product, string $path): Product;

    public function delete(Product $product): void;
}
