<?php

namespace App\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function resolveActiveStore(): ?object;

    public function paginateForAdmin(int $storeId, array $filters): LengthAwarePaginator;

    public function findForStore(int $storeId, int $id): ?Product;

    public function createForStore(int $storeId, array $payload): Product;

    public function update(Product $product, array $payload): Product;

    public function delete(Product $product): bool;

    public function adjustStock(Product $product, int $quantity): Product;
}
