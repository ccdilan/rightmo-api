<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function __construct(Product $resource, private ?string $imageUrl = null)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'category' => new CategoryResource($this->category), 'price' => $this->price,
            'rating' => (float) $this->rating, 'image_url' => $this->imageUrl,
            'created_at' => $this->created_at->toISOString(), 'updated_at' => $this->updated_at->toISOString()];
    }
}
