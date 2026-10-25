<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\ImageStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadProductImageRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductImageService;
use Illuminate\Http\UploadedFile;

class ProductImageController extends Controller
{
    public function __invoke(UploadProductImageRequest $request, int $product, ProductImageService $service, ImageStorage $images): ProductResource
    {
        /** @var UploadedFile $file */
        $file = $request->file('image');
        $result = $service->replace($product, $file);

        return new ProductResource($result, $images->url($result->image_path));
    }
}
