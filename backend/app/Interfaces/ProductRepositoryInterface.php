<?php

namespace App\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginateForAdmin(array $filters): LengthAwarePaginator;

    public function findWithDefaultVariant(int $id): ?Product;

    public function find(int $id): ?Product;

    public function create(array $payload): Product;

    public function update(Product $product, array $payload): Product;

    public function delete(Product $product): bool;
}
