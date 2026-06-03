<?php

namespace App\Interfaces;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

interface ProductVariantRepositoryInterface
{
    public function findProduct(int $productId): ?Product;

    public function allForProduct(Product $product): Collection;

    public function findForProduct(Product $product, int $id): ?ProductVariant;

    public function create(Product $product, array $payload): ProductVariant;

    public function update(Product $product, ProductVariant $variant, array $payload): ProductVariant;

    public function delete(Product $product, ProductVariant $variant): bool;
}
