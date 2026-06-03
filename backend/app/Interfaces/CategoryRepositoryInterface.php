<?php

namespace App\Interfaces;

use App\Models\Category;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    public function resolveActiveStore(): ?object;

    public function allForStore(int $storeId): Collection;

    public function findForStore(int $storeId, int $id): ?Category;

    public function createForStore(int $storeId, array $payload): Category;

    public function update(Category $category, array $payload): Category;

    public function delete(Category $category): bool;
}
