<?php

namespace App\Repositories;

use App\Interfaces\StorefrontRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorefrontRepository implements StorefrontRepositoryInterface
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

    public function activeCategories(int $storeId): Collection
    {
        return DB::table('categories')
            ->select([
                'id',
                'store_setting_id',
                'parent_category_id',
                'name',
                'slug',
                'description',
                'sort_order',
            ])
            ->where('store_setting_id', $storeId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function activeCategoryReferences(int $storeId): Collection
    {
        return DB::table('categories')
            ->select(['id', 'parent_category_id', 'store_setting_id', 'is_active'])
            ->where('store_setting_id', $storeId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function activeProducts(object $store, array $categoryIds = []): Collection
    {
        $productsQuery = DB::table('products')
            ->leftJoin('product_images', function ($join) {
                $join->on('products.id', '=', 'product_images.product_id')
                    ->where('product_images.is_primary', true);
            })
            ->join('categories', function ($join) use ($store) {
                $join->on('products.category_id', '=', 'categories.id')
                    ->where('categories.store_setting_id', $store->id);
            })
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.short_description',
                'products.price',
                'products.sale_price',
                'products.quantity',
                'products.weight',
                'products.category_id',
                'categories.name as category_name',
                'product_images.image',
            ])
            ->where('products.status', true);

        if ($categoryIds) {
            $productsQuery->whereIn('products.category_id', $categoryIds);
        }

        return $productsQuery->orderBy('products.name')->get()->map(function (object $product): object {
            $product->image = $product->image
                ? asset('storage/'.ltrim($product->image, '/'))
                : asset('images/product-placeholder.svg');

            return $product;
        });
    }

    public function findProductBySlug(object $store, string $slug): ?object
    {
        $product = DB::table('products')
            ->leftJoin('product_images', function ($join) {
                $join->on('products.id', '=', 'product_images.product_id')
                    ->where('product_images.is_primary', true);
            })
            ->join('categories', function ($join) use ($store) {
                $join->on('products.category_id', '=', 'categories.id')
                    ->where('categories.store_setting_id', $store->id);
            })
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.sku',
                'products.short_description',
                'products.description',
                'products.price',
                'products.sale_price',
                'products.quantity',
                'products.weight',
                'products.category_id',
                'categories.name as category_name',
                'product_images.image',
            ])
            ->where('products.status', true)
            ->where('products.slug', $slug)
            ->first();

        if ($product) {
            $product->image = $product->image
                ? asset('storage/' . ltrim($product->image, '/'))
                : asset('images/product-placeholder.svg');
        }

        return $product;
    }

    public function resolveCategoryDescendants(Collection $categories, array $selectedIds): array
    {
        $allIds = [];
        $childrenByParent = $categories->groupBy('parent_category_id');
        $stack = $selectedIds;

        while (!empty($stack)) {
            $id = array_pop($stack);

            if (in_array($id, $allIds, true)) {
                continue;
            }

            $allIds[] = $id;

            $children = $childrenByParent[$id] ?? collect();
            foreach ($children as $child) {
                $stack[] = $child->id;
            }
        }

        return $allIds;
    }
}
