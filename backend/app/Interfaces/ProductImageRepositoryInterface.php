<?php

namespace App\Interfaces;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

interface ProductImageRepositoryInterface
{
    public function findProduct(int $productId): ?Product;

    public function allForProduct(Product $product): Collection;

    public function findForProduct(Product $product, int $id): ?ProductImage;

    public function create(Product $product, UploadedFile $image, array $payload): ProductImage;

    public function update(Product $product, ProductImage $image, array $payload): ProductImage;

    public function delete(Product $product, ProductImage $image): bool;
}
