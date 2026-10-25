<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_generated_docs_cover_routes_and_contracts_without_a_database(): void
    {
        $directory = storage_path('framework/testing/scribe-'.uniqid());
        config(['scribe.static.output_path' => $directory.'/site', 'database.connections.mysql.port' => 1]);
        DB::purge('mysql');

        try {
            $this->artisan('scribe:generate', ['--force' => true, '--no-interaction' => true, '--scribe-dir' => $directory.'/cache'])->assertSuccessful();

            $spec = Yaml::parseFile($directory.'/site/openapi.yaml');
            $this->assertSame('3.1.0', $spec['openapi']);
            $operationIds = [];
            foreach (Route::getRoutes() as $route) {
                if (! str_starts_with($route->uri(), 'api/v1/')) {
                    continue;
                }
                foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                    $operation = $spec['paths']['/'.$route->uri()][strtolower($method)] ?? null;
                    $this->assertIsArray($operation, $method.' '.$route->uri().' is missing from the documentation.');
                    $operationIds[] = $operation['operationId'];
                    $this->assertNotEmpty($operation['responses']);
                }
            }
            $this->assertSame($operationIds, array_values(array_unique($operationIds)));
            $this->assertSame([], $spec['paths']['/api/v1/auth/login']['post']['security']);
            $this->assertSame('bearer', $spec['components']['securitySchemes']['default']['scheme']);
            $this->assertSame([['default' => []]], $spec['security']);

            $category = $spec['paths']['/api/v1/categories/{category}'];
            $this->assertSame(['name'], $category['put']['requestBody']['content']['application/json']['schema']['required']);
            $this->assertEmpty($category['patch']['requestBody']['content']['application/json']['schema']['required'] ?? []);
            $this->assertArrayHasKey('409', $category['delete']['responses']);
            $this->assertArrayHasKey('products_count', $category['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['properties']);
            $product = $spec['paths']['/api/v1/products/{product}'];
            $put = $product['put']['requestBody']['content']['application/json']['schema'];
            $patch = $product['patch']['requestBody']['content']['application/json']['schema'];
            $this->assertEqualsCanonicalizing(['name', 'description', 'category_id', 'price', 'rating'], $put['required']);
            $this->assertSame(['string', 'null'], $put['properties']['description']['type']);
            $this->assertEmpty($patch['required'] ?? []);
            $fields = $product['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['properties'];
            $this->assertSame('string', $fields['price']['type']);
            $this->assertSame(['string', 'null'], $fields['image_url']['type']);
            $this->assertSame(['string', 'null'], $fields['description']['type']);

            $listing = $spec['paths']['/api/v1/products']['get'];
            $this->assertArrayNotHasKey('requestBody', $listing);
            $this->assertEqualsCanonicalizing(['search', 'category_id', 'category_ids', 'min_rating', 'min_price', 'max_price', 'sort_by', 'sort_direction', 'page', 'per_page'], array_column($listing['parameters'], 'name'));
            $upload = $spec['paths']['/api/v1/products/{product}/image']['post']['requestBody']['content']['multipart/form-data']['schema'];
            $this->assertSame('binary', $upload['properties']['image']['format']);
            $this->assertSame(['image'], $upload['required']);
            $this->assertStringContainsString('Rightmo API', File::get($directory.'/site/index.html'));
            $this->assertSame('https://schema.getpostman.com/json/collection/v2.1.0/collection.json', json_decode(File::get($directory.'/site/collection.json'), true, flags: JSON_THROW_ON_ERROR)['info']['schema']);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
