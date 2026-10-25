<?php

namespace Tests\Feature;

use App\Contracts\ProductRepository;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProductConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_replacements_leave_only_the_committed_image(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $product = Product::factory()->create(['image_path' => 'products/old.png']);
        $disk->put('products/old.png', 'old');
        $source = UploadedFile::fake()->image('source.png');
        $config = config('database.connections.mysql');
        $environment = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => $config['host'], 'DB_PORT' => (string) $config['port'], 'DB_DATABASE' => $config['database'], 'DB_USERNAME' => $config['username'], 'DB_PASSWORD' => $config['password'], 'CACHE_STORE' => 'array'];
        $processes = [];
        DB::beginTransaction();
        try {
            $this->app->make(ProductRepository::class)->lock($product->id);
            for ($i = 0; $i < 2; $i++) {
                $process = new Process([PHP_BINARY, '-d', 'memory_limit=256M', base_path('tests/Support/replace-image.php'), (string) $product->id, $disk->path(''), $source->getPathname()], base_path(), $environment, timeout: 15);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 10;
            do {
                $ready = count(array_filter($processes, fn (Process $p): bool => str_contains($p->getOutput(), 'READY')));
                if ($ready === 2) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertSame(2, $ready, 'Both uploads should reach the locked product.');
            foreach ($processes as $process) {
                $this->assertTrue($process->isRunning());
            }
            DB::commit();
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            }
            $path = $product->fresh()->image_path;
            $this->assertSame([$path], $disk->allFiles('products'));
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
        }
    }
}
