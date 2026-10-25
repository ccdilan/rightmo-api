<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $category_id
 * @property string $price
 * @property string $rating
 * @property string|null $image_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category $category
 */
#[Fillable(['name', 'description', 'category_id', 'price', 'rating', 'image_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'rating' => 'decimal:1'];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
