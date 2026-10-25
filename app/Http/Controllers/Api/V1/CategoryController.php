<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\ProductService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Categories
 *
 * Categories are seeded and shared by all users.
 */
class CategoryController extends Controller
{
    /**
     * List categories
     *
     * Returns available categories ordered by name.
     *
     * @responseFile 200 docs/responses/categories.json
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function __invoke(ProductService $products): AnonymousResourceCollection
    {
        return CategoryResource::collection($products->categories());
    }
}
