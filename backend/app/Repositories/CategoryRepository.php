<?php

namespace App\Repositories;

use App\Interfaces\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function resolveActiveStore(): ?object
    {
        $store = DB::table('store_settings')
            ->select(['id', 'store_name', 'layout', 'is_active'])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (!$store) {
            $store = DB::table('store_settings')
                ->select(['id', 'store_name', 'layout', 'is_active'])
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();
        }

        return $store;
    }

    public function allForStore(int $storeId): Collection
    {
        return Category::where('store_setting_id', $storeId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findForStore(int $storeId, int $id): ?Category
    {
        return Category::where('store_setting_id', $storeId)
            ->where('id', $id)
            ->first();
    }

    public function createForStore(int $storeId, array $payload): Category
    {
        return Category::create([
            'store_setting_id' => $storeId,
            'parent_category_id' => $payload['parent_category_id'] ?? null,
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'description' => $payload['description'] ?? null,
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => $payload['is_active'] ?? true,
        ]);
    }

    public function update(Category $category, array $payload): Category
    {
        $wasActive = (bool) $category->is_active;

        $category->update([
            'parent_category_id' => $payload['parent_category_id'] ?? null,
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'description' => $payload['description'] ?? null,
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : $category->is_active,
        ]);

        if ($wasActive && !$category->is_active) {
            $category->deactivateDescendants();
        }

        return $category;
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }
}
