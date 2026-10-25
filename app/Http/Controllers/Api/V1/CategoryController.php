<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @group Categories
 *
 * All authenticated users can manage shared categories. Only empty categories can be deleted.
 */
class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    /**
     * List categories
     *
     * Returns categories ordered by name, with current product counts.
     *
     * @responseFile 200 docs/responses/categories.json
     *
     * @response 401 {"message":"Unauthenticated."}
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->categories->list());
    }

    /**
     * Get a category
     *
     * @urlParam category integer required Category ID. Example: 1
     *
     * @responseFile 200 docs/responses/category.json
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     */
    public function show(int $category): CategoryResource
    {
        return new CategoryResource($this->categories->find($category));
    }

    /**
     * Create a category
     *
     * Names are trimmed and case-insensitively unique.
     *
     * @responseFile 201 docs/responses/category.json
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 422 {"message":"A category with this name already exists.","errors":{"name":["A category with this name already exists."]}}
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        return (new CategoryResource($this->categories->create($request->validated('name'))))->response()->setStatusCode(201);
    }

    /**
     * Update a category
     *
     * PUT requires name. PATCH preserves an omitted name. Existing product assignments remain intact.
     *
     * @urlParam category integer required Category ID. Example: 1
     *
     * @responseFile 200 docs/responses/category.json
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     * @response 422 {"message":"A category with this name already exists.","errors":{"name":["A category with this name already exists."]}}
     */
    public function update(UpdateCategoryRequest $request, int $category): CategoryResource
    {
        return new CategoryResource($this->categories->update($category, $request->validated('name')));
    }

    /**
     * Delete a category
     *
     * Only empty categories can be deleted. Move products to another category first.
     * Row locking and the database foreign key prevent removal of a referenced category.
     *
     * @urlParam category integer required Category ID. Example: 1
     *
     * @response 204
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     * @response 409 {"message":"This category contains products. Move or delete those products before deleting the category."}
     */
    public function destroy(int $category): Response
    {
        $this->categories->delete($category);

        return response()->noContent();
    }
}
