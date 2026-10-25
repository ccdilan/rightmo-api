<?php

namespace Tests\Feature;

use App\Contracts\CategoryRepository;
use App\Exceptions\CategoryInUse;
use App\Exceptions\DuplicateCategoryName;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_be_created_read_renamed_and_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $id = $this->postJson('/api/v1/categories', ['name' => '  Home office  ', 'id' => 999])->assertCreated()->assertJsonPath('data.name', 'Home office')->assertJsonPath('data.products_count', 0)->json('data.id');
        $this->getJson('/api/v1/categories/'.$id)->assertOk()->assertJsonPath('data.name', 'Home office');
        $this->patchJson('/api/v1/categories/'.$id, ['name' => 'Home office'])->assertOk();
        $this->patchJson('/api/v1/categories/'.$id, [])->assertOk()->assertJsonPath('data.name', 'Home office');
        $this->putJson('/api/v1/categories/'.$id, [])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->putJson('/api/v1/categories/'.$id, ['name' => '  Accessories  '])->assertOk()->assertJsonPath('data.name', 'Accessories');
        $this->assertDatabaseHas('categories', ['id' => $id, 'name' => 'Accessories']);
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonFragment(['id' => $id, 'name' => 'Accessories', 'products_count' => 0]);
        $this->deleteJson('/api/v1/categories/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $id]);
    }

    public function test_renaming_preserves_products_and_referenced_categories_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create(['name' => 'Old category']);
        $product = Product::factory()->for($category)->create();
        $this->patchJson('/api/v1/categories/'.$category->id, ['name' => 'Renamed'])->assertOk()->assertJsonPath('data.products_count', 1);
        $this->getJson('/api/v1/products/'.$product->id)->assertOk()->assertJsonPath('data.category.name', 'Renamed');
        $this->deleteJson('/api/v1/categories/'.$category->id)->assertStatus(409)->assertExactJson(['message' => 'This category contains products. Move or delete those products before deleting the category.']);
        $this->assertModelExists($category);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id]);
        $other = Category::factory()->create();
        $this->patchJson('/api/v1/products/'.$product->id, ['category_id' => $other->id])->assertOk();
        $this->deleteJson('/api/v1/categories/'.$category->id)->assertNoContent();
        $this->assertModelExists($product);
    }

    public function test_names_are_trimmed_case_insensitively_unique_and_sorted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $audio = Category::factory()->create(['name' => 'Audio']);
        $other = Category::factory()->create(['name' => 'Workspace']);
        $this->postJson('/api/v1/categories', ['name' => ' audio '])->assertUnprocessable()->assertJsonPath('errors.name.0', 'A category with this name already exists.');
        $this->patchJson('/api/v1/categories/'.$other->id, ['name' => 'AUDIO'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->getJson('/api/v1/categories')->assertOk()->assertJsonPath('data.0.id', $audio->id)->assertJsonPath('data.1.id', $other->id);
    }

    #[DataProvider('invalidNames')]
    public function test_invalid_names_are_rejected(mixed $name): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/categories', ['name' => $name])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('categories', 0);
    }

    public static function invalidNames(): array
    {
        return [['  '], [null], [str_repeat('x', 121)], [['invalid']], [123]];
    }

    public function test_missing_categories_return_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());
        foreach (['GET', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->json($method, '/api/v1/categories/999999', ['name' => 'Missing'])->assertNotFound();
        }
    }

    public function test_database_duplicate_create_is_translated(): void
    {
        Category::factory()->create(['name' => 'Audio']);
        $this->expectException(DuplicateCategoryName::class);
        $this->app->make(CategoryRepository::class)->create('AUDIO');
    }

    public function test_database_duplicate_update_is_translated(): void
    {
        Category::factory()->create(['name' => 'Audio']);
        $other = Category::factory()->create();
        $this->expectException(DuplicateCategoryName::class);
        $this->app->make(CategoryRepository::class)->update($other, 'AUDIO');
    }

    public function test_database_foreign_key_is_translated_when_a_product_reference_races_deletion(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();
        $this->expectException(CategoryInUse::class);
        $this->app->make(CategoryRepository::class)->delete($category);
    }
}
