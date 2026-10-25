<?php

namespace App\Services;

use App\Contracts\ProductRepository;
use App\Data\ProductData;
use App\Data\ProductQuery;
use App\Models\Product;
use Illuminate\Database\DatabaseManager;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductService
{
    public function __construct(private ProductRepository $products, private ProductImageService $images, private DatabaseManager $database) {}

    /** @return LengthAwarePaginator<int, Product> */
    public function list(ProductQuery $query): LengthAwarePaginator
    {
        return $this->products->paginate($query);
    }

    public function find(int $id): Product
    {
        return $this->products->find($id);
    }

    public function create(ProductData $data): Product
    {
        return $this->database->transaction(fn () => $this->products->create($data));
    }

    public function update(int $id, ProductData $data): Product
    {
        return $this->database->transaction(fn () => $this->products->update($this->products->lock($id), $data));
    }

    public function delete(int $id): void
    {
        $this->database->transaction(function () use ($id): void {
            $product = $this->products->lock($id);
            $path = $product->image_path;
            $this->products->delete($product);
            $this->database->afterCommit(fn () => $this->images->removeUnused($path));
        });
    }
}
