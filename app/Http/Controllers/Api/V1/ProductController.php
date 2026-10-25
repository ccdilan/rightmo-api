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

/**
 * @group Products
 *
 * All authenticated users can read and modify the shared catalog.
 */
class ProductController extends Controller
{
    public function __construct(private ProductService $products, private ImageStorage $images) {}

    /**
     * List products
     *
     * Combine literal, case-insensitive name search with category and price filters.
     * Default ordering is newest first. All sort orders include an ID tie-breaker.
     * Pagination defaults to 12 items; per_page is capped at 100.
     *
     * @responseFile 200 docs/responses/products.json
     *
     * @responseField data object[] required Products on the current page.
     * @responseField data.price string required Nonnegative decimal string with two places.
     * @responseField data.description string|null required Product description, or null.
     * @responseField data.image_url string|null required Public image URL, or null.
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 422 {"message":"The max price field must be greater than or equal to min price.","errors":{"max_price":["The max price field must be greater than or equal to min price."]}}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->products->list(ProductQuery::fromArray($request->validated()));
        $products->setCollection($products->getCollection()->map(fn (Product $product): ProductResource => $this->resource($product)));

        return ProductResource::collection($products);
    }

    /**
     * Get a product
     *
     * Returns full details with its category and a public image URL.
     *
     * @urlParam product integer required Product ID. Example: 1
     *
     * @responseFile 200 docs/responses/product.json
     *
     * @responseField data.price string required Nonnegative decimal string with two places.
     * @responseField data.description string|null required Product description, or null.
     * @responseField data.image_url string|null required Public image URL, or null. Storage paths are never exposed.
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function show(int $product): ProductResource
    {
        return $this->resource($this->products->find($product));
    }

    /**
     * Create a product
     *
     * Names are trimmed and must be unique without regard to case.
     * Upload an image separately after creating the product.
     *
     * @responseFile 201 docs/responses/product.json
     *
     * @responseField data.price string required Nonnegative decimal string with two places.
     * @responseField data.description string|null required Product description, or null.
     * @responseField data.image_url string|null required Public image URL, or null. Storage paths are never exposed.
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 422 {"message":"A product with this name already exists.","errors":{"name":["A product with this name already exists."]}}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return $this->resource($this->products->create(ProductData::fromArray($request->validated())))->response()->setStatusCode(201);
    }

    /**
     * Update a product
     *
     * PUT replaces all editable fields and requires name, description (which may be null), category_id, price, and rating.
     * PATCH accepts any subset and preserves omitted fields. Unchanged names are valid.
     * Images are updated through the dedicated image endpoint.
     *
     * @urlParam product integer required Product ID. Example: 1
     *
     * @responseFile 200 docs/responses/product.json
     *
     * @responseField data.price string required Nonnegative decimal string with two places.
     * @responseField data.description string|null required Product description, or null.
     * @responseField data.image_url string|null required Public image URL, or null. Storage paths are never exposed.
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     * @response 422 {"message":"A product with this name already exists.","errors":{"name":["A product with this name already exists."]}}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function update(UpdateProductRequest $request, int $product): ProductResource
    {
        return $this->resource($this->products->update($product, ProductData::fromArray($request->validated())));
    }

    /**
     * Delete a product
     *
     * Deletes the product and cleans up its image after the database transaction commits.
     *
     * @urlParam product integer required Product ID. Example: 1
     *
     * @response 204
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
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
