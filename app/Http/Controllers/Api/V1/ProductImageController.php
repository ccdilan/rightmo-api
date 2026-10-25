<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\ImageStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductImageService;
use Illuminate\Http\UploadedFile;

/**
 * @group Product images
 *
 * One public image per product. Product data endpoints still require authentication.
 */
class ProductImageController extends Controller
{
    /**
     * Upload or replace an image
     *
     * Send multipart/form-data containing image. JPEG, PNG, and WebP content is accepted up to 5 MiB and 10,000 × 10,000 pixels.
     * The replacement uses a generated filename. Failed persistence removes the new file; the previous image is deleted after commit.
     *
     * @urlParam product integer required Product ID. Example: 1
     *
     * @responseFile 200 docs/responses/product-image.json
     *
     * @responseField data.price string required Nonnegative decimal string with two places.
     * @responseField data.description string|null required Product description, or null.
     * @responseField data.image_url string|null required Public image URL, or null. Storage paths are never exposed.
     *
     * @response 401 {"message":"Unauthenticated."}
     * @response 404 {"message":"Resource not found."}
     * @response 413 {"message":"The upload is too large."}
     * @response 422 {"message":"The image field must be an image.","errors":{"image":["The image field must be an image."]}}
     * @response 500 {"message":"An unexpected error occurred. Please try again."}
     */
    public function __invoke(UploadProductImageRequest $request, int $product, ProductImageService $service, ImageStorage $images): ProductResource
    {
        /** @var UploadedFile $file */
        $file = $request->file('image');
        $result = $service->replace($product, $file);

        return new ProductResource($result, $images->url($result->image_path));
    }
}
