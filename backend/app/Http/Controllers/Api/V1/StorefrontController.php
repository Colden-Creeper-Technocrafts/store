<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
}
