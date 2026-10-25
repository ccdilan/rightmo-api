<?php

namespace App\Storage;

use App\Contracts\ImageStorage;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class PublicImageStorage implements ImageStorage
{
    public function __construct(private Factory $filesystems) {}

    public function store(UploadedFile $file): string
    {
        $path = $this->filesystems->disk('public')->putFile('products', $file);
        if ($path === false) {
            throw new RuntimeException('The image could not be stored.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        if (! $this->filesystems->disk('public')->delete($path)) {
            throw new RuntimeException('The image could not be removed.');
        }
    }

    public function url(?string $path): ?string
    {
        return $path === null ? null : $this->filesystems->disk('public')->url($path);
    }
}
