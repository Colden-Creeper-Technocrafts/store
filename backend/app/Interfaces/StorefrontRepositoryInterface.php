<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface StorefrontRepositoryInterface
{
    public function resolveActiveStore(): ?object;

    public function activeCategories(int $storeId): Collection;

    public function activeCategoryReferences(int $storeId): Collection;

    public function activeProducts(object $store, array $categoryIds = []): Collection;

    public function resolveCategoryDescendants(Collection $categories, array $selectedIds): array;

    public function findProductBySlug(object $store, string $slug): ?object;
}
