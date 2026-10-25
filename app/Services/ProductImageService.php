<?php

namespace App\Services;

use App\Contracts\ImageStorage;
use App\Contracts\ProductRepository;
use App\Models\Product;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Psr\Log\LoggerInterface;
use Throwable;

final class ProductImageService
{
    public function __construct(private ProductRepository $products, private ImageStorage $images, private DatabaseManager $database, private LoggerInterface $logger) {}

    public function replace(int $id, UploadedFile $file): Product
    {
        $this->products->find($id);
        $path = $this->images->store($file);
        try {
            return $this->database->transaction(function () use ($id, $path): Product {
                $product = $this->products->lock($id);
                $oldPath = $product->image_path;
                $product = $this->products->setImage($product, $path);
                $this->database->afterCommit(fn () => $this->removeUnused($oldPath));

                return $product;
            });
        } catch (Throwable $exception) {
            $this->removeUnused($path);
            throw $exception;
        }
    }

    public function removeUnused(?string $path): void
    {
        if ($path === null) {
            return;
        }
        try {
            $this->images->delete($path);
        } catch (Throwable $exception) {
            $this->logger->warning('Product image cleanup failed.', ['path' => $path, 'exception' => $exception]);
        }
    }
}
