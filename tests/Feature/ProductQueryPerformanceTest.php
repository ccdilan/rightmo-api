<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_query_count_stays_bounded_with_more_products_and_selected_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $categories = Category::factory()->count(8)->create();
        foreach ($categories as $category) {
            Product::factory()->count(6)->for($category)->create();
        }
        foreach ([12, 48] as $pageSize) {
            foreach ([false, true] as $filtered) {
                $query = ['per_page' => $pageSize];
                if ($filtered) {
                    $query['category_ids'] = $categories->modelKeys();
                }
                DB::flushQueryLog();
                DB::enableQueryLog();
                try {
                    $response = $this->getJson('/api/v1/products?'.http_build_query($query));
                    $queries = DB::getQueryLog();
                } finally {
                    DB::disableQueryLog();
                    DB::flushQueryLog();
                }
                $response->assertOk()->assertJsonCount($pageSize, 'data')->assertJsonPath('meta.total', 48);
                $this->assertCount($filtered ? 4 : 3, $queries, 'Expected count, page and eager-loaded categories; multi-category validation adds one batched query.');
                foreach ($response->json('data') as $product) {
                    $this->assertNotEmpty($product['category']['name']);
                }
            }
        }
    }
}
