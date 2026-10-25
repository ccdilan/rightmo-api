<?php

namespace Tests\Feature;

use App\Contracts\ImageStorage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use DatabaseMigrations;

    public function test_upload_replaces_the_image_and_delete_cleans_it_up(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['image_path' => 'products/old.png']);
        Storage::disk('public')->put('products/old.png', 'old');
        $response = $this->postJson('/api/v1/products/'.$product->id.'/image', ['image' => UploadedFile::fake()->image('new.png')])->assertOk()->assertJsonMissingPath('data.image_path');
        $path = $product->fresh()->image_path;
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('products/old.png');
        $this->assertStringContainsString($path, $response->json('data.image_url'));
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('data.0.image_url', $response->json('data.image_url'));
        $this->deleteJson('/api/v1/products/'.$product->id)->assertNoContent();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_upload_rejects_invalid_content_and_oversized_images(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create();
        foreach ([UploadedFile::fake()->create('fake.png', 100, 'text/plain'), UploadedFile::fake()->image('large.png')->size(5121), UploadedFile::fake()->createWithContent('script.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')] as $file) {
            $this->postJson('/api/v1/products/'.$product->id.'/image', ['image' => $file])->assertUnprocessable()->assertJsonValidationErrors('image');
        }
        $this->assertNull($product->fresh()->image_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_database_write_preserves_old_image_and_removes_new_upload(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['image_path' => 'products/old.png']);
        Storage::disk('public')->put('products/old.png', 'old');
        DB::unprepared("CREATE TRIGGER reject_image_update BEFORE UPDATE ON products FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Simulated write failure'");
        try {
            $this->postJson('/api/v1/products/'.$product->id.'/image', ['image' => UploadedFile::fake()->image('new.png')])->assertStatus(500)->assertExactJson(['message' => 'An unexpected error occurred. Please try again.']);
            $this->assertSame('products/old.png', $product->fresh()->image_path);
            $this->assertSame(['products/old.png'], Storage::disk('public')->allFiles());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS reject_image_update');
        }
    }

    public function test_cleanup_failure_does_not_undo_a_successful_update(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['image_path' => 'products/old.png']);
        $images = $this->mock(ImageStorage::class);
        $images->shouldReceive('store')->once()->andReturn('products/new.png');
        $images->shouldReceive('delete')->once()->with('products/old.png')->andThrow(new \RuntimeException('disk unavailable'));
        $images->shouldReceive('url')->once()->with('products/new.png')->andReturn('/storage/products/new.png');
        $this->postJson('/api/v1/products/'.$product->id.'/image', ['image' => UploadedFile::fake()->image('new.png')])->assertOk();
        $this->assertSame('products/new.png', $product->fresh()->image_path);
    }

    public function test_missing_product_does_not_store_an_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/products/999999/image', ['image' => UploadedFile::fake()->image('new.png')])->assertNotFound();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
