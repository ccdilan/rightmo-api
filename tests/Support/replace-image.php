<?php

use App\Contracts\ImageStorage;
use App\Services\ProductImageService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['filesystems.disks.public.root' => $argv[2]]);
$storage = $app->make(ImageStorage::class);
$app->instance(ImageStorage::class, new class($storage) implements ImageStorage
{
    public function __construct(private ImageStorage $storage) {}

    public function store(UploadedFile $file): string
    {
        $path = $this->storage->store($file);
        fwrite(STDOUT, "READY\n");

        return $path;
    }

    public function delete(string $path): void
    {
        $this->storage->delete($path);
    }

    public function url(?string $path): ?string
    {
        return $this->storage->url($path);
    }
});
$app->make(ProductImageService::class)->replace((int) $argv[1], new UploadedFile($argv[3], 'image.png', 'image/png', null, true));
