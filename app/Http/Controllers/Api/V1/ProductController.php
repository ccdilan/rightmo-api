<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\ImageStorage;
use App\Data\ProductData;
use App\Data\ProductQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $products, private ImageStorage $images) {}

    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->products->list(ProductQuery::fromArray($request->validated()));
        $products->setCollection($products->getCollection()->map(fn (Product $product): ProductResource => $this->resource($product)));

        return ProductResource::collection($products);
    }

    public function show(int $product): ProductResource
    {
        return $this->resource($this->products->find($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->resource($this->products->create(ProductData::fromArray($request->validated())))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, int $product): ProductResource
    {
        return $this->resource($this->products->update($product, ProductData::fromArray($request->validated())));
    }

    public function destroy(int $product): Response
    {
        $this->products->delete($product);

        return response()->noContent();
    }

    private function resource(Product $product): ProductResource
    {
        return new ProductResource($product, $this->images->url($product->image_path));
    }
}
