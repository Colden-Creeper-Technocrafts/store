<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class StorefrontController extends Controller
{
    private function resolveActiveStore(): ?object
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

    private function normalizedLayout(?string $layout): string
    {
        $value = strtolower((string) $layout);

        return in_array($value, ['ladies', 'grocery'], true) ? $value : 'ladies';
    }

    private function buildCategoryTree(Collection $categories, ?int $parentId = null): array
    {
        return $categories
            ->where('parent_category_id', $parentId)
            ->sortBy('sort_order')
            ->values()
            ->map(function (object $category) use ($categories): array {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'children' => $this->buildCategoryTree($categories, $category->id),
                ];
            })
            ->all();
    }

    public function show(): JsonResponse
    {
        try {
            $store = $this->resolveActiveStore();
        } catch (QueryException) {
            $store = null;
        }

        $layout = $this->normalizedLayout($store->layout ?? null);

        return response()->json([
            'success' => true,
            'store' => [
                'name' => (string) ($store->store_name ?? 'Kumkum Novelty Store'),
                'layout' => $layout,
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        try {
            $store = $this->resolveActiveStore();

            if (!$store) {
                return response()->json([
                    'success' => true,
                    'store' => null,
                    'categories' => [],
                ]);
            }

            $categories = DB::table('categories')
                ->select([
                    'id',
                    'store_setting_id',
                    'parent_category_id',
                    'name',
                    'slug',
                    'description',
                    'sort_order',
                ])
                ->where('store_setting_id', $store->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } catch (QueryException) {
            return response()->json([
                'success' => true,
                'store' => null,
                'categories' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'store' => [
                'id' => $store->id,
                'name' => (string) $store->store_name,
                'layout' => $this->normalizedLayout($store->layout ?? null),
            ],
            'categories' => $this->buildCategoryTree($categories),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        try {
            $store = $this->resolveActiveStore();

            if (!$store) {
                return response()->json([
                    'success' => true,
                    'store' => null,
                    'products' => [],
                ]);
            }

            $categories = DB::table('categories')
                ->select(['id', 'parent_category_id', 'store_setting_id', 'is_active'])
                ->where('store_setting_id', $store->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $categoryIds = $request->query('category_ids', []);

            if (!is_array($categoryIds)) {
                $categoryIds = array_filter(explode(',', (string) $categoryIds), 'strlen');
            }

            $categoryIds = array_map('intval', $categoryIds);
            $categoryIds = array_filter($categoryIds, fn ($id) => $id > 0);

            if ($categoryIds) {
                $categoryIds = $this->resolveCategoryDescendants($categories, $categoryIds);
            }

            $productsQuery = DB::table('products')
                ->leftJoin('product_images', function ($join) {
                    $join->on('products.id', '=', 'product_images.product_id')
                        ->where('product_images.is_primary', true);
                })
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
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

            $products = $productsQuery->orderBy('products.name')->get();
        } catch (QueryException) {
            return response()->json([
                'success' => true,
                'store' => null,
                'products' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'store' => [
                'id' => $store->id,
                'name' => (string) $store->store_name,
                'layout' => $this->normalizedLayout($store->layout ?? null),
            ],
            'products' => $products,
        ]);
    }

    private function resolveCategoryDescendants(Collection $categories, array $selectedIds): array
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
