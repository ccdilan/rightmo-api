<?php

namespace Tests\Feature;

use App\Contracts\ProductRepository;
use App\Data\ProductData;
use App\Exceptions\DuplicateProductName;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_crud_preserves_omitted_fields_and_has_a_public_response(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $data = ['name' => '  Studio Headphones  ', 'description' => 'Warm sound.', 'category_id' => $category->id, 'price' => '99.90', 'rating' => 4.5];
        $id = $this->postJson('/api/v1/products', $data)->assertCreated()->assertJsonPath('data.name', 'Studio Headphones')->assertJsonPath('data.price', '99.90')->assertJsonMissingPath('data.image_path')->json('data.id');
        $this->getJson('/api/v1/products/'.$id)->assertOk()->assertJsonPath('data.category.id', $category->id);
        $this->patchJson('/api/v1/products/'.$id, ['price' => '105.50'])->assertOk()->assertJsonPath('data.description', 'Warm sound.');
        $this->patchJson('/api/v1/products/'.$id, ['description' => null])->assertOk()->assertJsonPath('data.description', null);
        $this->patchJson('/api/v1/products/'.$id, ['name' => 'Studio Headphones'])->assertOk();
        $this->putJson('/api/v1/products/'.$id, ['name' => 'Only name'])->assertUnprocessable();
        $this->putJson('/api/v1/products/'.$id, [...$data, 'name' => 'Replaced', 'description' => null])->assertOk()->assertJsonPath('data.name', 'Replaced');
        $this->assertDatabaseHas('products', ['id' => $id, 'name' => 'Replaced']);
        $this->deleteJson('/api/v1/products/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('products', ['id' => $id]);
        $this->getJson('/api/v1/products/'.$id)->assertNotFound()->assertExactJson(['message' => 'Resource not found.']);
    }

    public function test_names_are_trimmed_and_case_insensitively_unique(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['name' => 'Studio']);
        $data = ['name' => ' studio ', 'category_id' => $product->category_id, 'price' => 1, 'rating' => 0];
        $this->postJson('/api/v1/products', $data)->assertUnprocessable()->assertJsonPath('errors.name.0', 'A product with this name already exists.');
        $other = Product::factory()->create();
        $this->patchJson('/api/v1/products/'.$other->id, ['name' => 'STUDIO'])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_database_constraint_is_translated_when_validation_is_raced(): void
    {
        $product = Product::factory()->create(['name' => 'Unique']);
        $repository = $this->app->make(ProductRepository::class);
        $this->expectException(DuplicateProductName::class);
        $repository->create(ProductData::fromArray(['name' => 'UNIQUE', 'category_id' => $product->category_id, 'price' => '1', 'rating' => '1']));
    }

    public function test_filters_search_sort_and_pagination_work_together(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $first = Product::factory()->for($category)->create(['name' => 'Studio A', 'price' => 50, 'rating' => 4]);
        $second = Product::factory()->for($category)->create(['name' => 'Studio B', 'price' => 50, 'rating' => 5]);
        Product::factory()->for($category)->create(['name' => 'Studio Expensive', 'price' => 150]);
        Product::factory()->create(['name' => 'Studio Elsewhere', 'price' => 50]);
        Product::factory()->for($category)->create(['name' => 'Other Name', 'price' => 50]);
        $query = http_build_query(['search' => 'STUDIO', 'category_id' => $category->id, 'min_price' => 40, 'max_price' => 100, 'sort_by' => 'price', 'sort_direction' => 'asc', 'per_page' => 1]);
        $this->getJson('/api/v1/products?'.$query)->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $first->id)->assertJsonStructure(['links', 'meta']);
        $this->getJson('/api/v1/products?'.$query.'&page=2')->assertOk()->assertJsonPath('data.0.id', $second->id);
        $this->getJson('/api/v1/products?sort_by=rating&sort_direction=desc&search=Studio%20B')->assertOk()->assertJsonPath('data.0.rating', 5);
        $this->getJson('/api/v1/products?search=no-such-product')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonFragment(['id' => $category->id, 'name' => $category->name]);
    }

    public function test_multiple_categories_words_and_rating_combine_with_price_and_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $categories = Category::factory()->count(3)->create();
        $first = Product::factory()->for($categories[0])->create(['name' => 'Wireless Studio Mini', 'price' => 60, 'rating' => 4.5]);
        $second = Product::factory()->for($categories[1])->create(['name' => 'Studio Wireless Pro', 'price' => 60, 'rating' => 5]);
        Product::factory()->for($categories[2])->create(['name' => 'Studio Wireless Elsewhere', 'price' => 60, 'rating' => 5]);
        Product::factory()->for($categories[0])->create(['name' => 'Studio Wired', 'price' => 60, 'rating' => 5]);
        Product::factory()->for($categories[1])->create(['name' => 'Studio Wireless Low', 'price' => 60, 'rating' => 3]);
        Product::factory()->for($categories[0])->create(['name' => 'Studio Wireless Expensive', 'price' => 160, 'rating' => 5]);
        $query = http_build_query(['search' => " WIRELESS \t studio ", 'category_ids' => [$categories[0]->id, $categories[1]->id], 'min_rating' => 4.5, 'min_price' => 50, 'max_price' => 100, 'sort_by' => 'price', 'sort_direction' => 'asc', 'per_page' => 1]);
        $this->getJson('/api/v1/products?'.$query)->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $first->id);
        $this->getJson('/api/v1/products?'.$query.'&page=2')->assertOk()->assertJsonPath('data.0.id', $second->id);
        $this->getJson('/api/v1/products?category_id='.$categories[0]->id.'&min_rating=5')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_category_selection_rejects_conflicting_or_duplicate_ids(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $this->getJson('/api/v1/products?'.http_build_query(['category_id' => $category->id, 'category_ids' => [$category->id]]))->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->getJson('/api/v1/products?'.http_build_query(['category_ids' => [$category->id, $category->id]]))->assertUnprocessable()->assertJsonValidationErrors('category_ids.0');
    }

    public function test_search_treats_wildcards_and_sql_as_literal_text(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['name' => '100% Pure']);
        Product::factory()->create(['name' => '1000 Pure']);
        $this->getJson('/api/v1/products?search='.urlencode('100%'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $product->id);
        $this->getJson('/api/v1/products?search='.urlencode("' OR 1=1 --"))->assertOk()->assertJsonCount(0, 'data');
    }

    #[DataProvider('invalidQueries')]
    public function test_invalid_listing_parameters_return_422(string $query, string $field): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/products?'.$query)->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function invalidQueries(): array
    {
        return [['min_price=100&max_price=20', 'max_price'], ['sort_by=password', 'sort_by'], ['sort_direction=sideways', 'sort_direction'], ['per_page=101', 'per_page'], ['page=0', 'page'], ['category_id=999999', 'category_id'], ['min_price=-1', 'min_price'], ['min_rating=5.1', 'min_rating'], ['min_rating=-1', 'min_rating'], ['min_rating=4.55', 'min_rating'], ['category_ids=1', 'category_ids'], ['category_ids[]=999999', 'category_ids'], ['category_ids[a]=1', 'category_ids'], ['category_ids[][]=1', 'category_ids.0'], ['search=one+two+three+four+five+six+seven+eight+nine', 'search']];
    }

    public function test_invalid_product_values_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/products', ['name' => '', 'category_id' => 999999, 'price' => -1, 'rating' => 6])->assertUnprocessable()->assertJsonValidationErrors(['name', 'category_id', 'price', 'rating']);
        $product = Product::factory()->create();
        $this->patchJson('/api/v1/products/'.$product->id, ['price' => '1.234', 'rating' => '4.55'])->assertUnprocessable()->assertJsonValidationErrors(['price', 'rating']);
    }

    public function test_missing_product_write_operations_return_404(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->patchJson('/api/v1/products/999999', ['name' => 'Missing'])->assertNotFound();
        $this->deleteJson('/api/v1/products/999999')->assertNotFound();
    }
}
