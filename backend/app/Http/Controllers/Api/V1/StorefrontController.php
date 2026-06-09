<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorefrontProductsRequest;
use App\Interfaces\StorefrontRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;

class StorefrontController extends Controller
{
    public function __construct(private readonly StorefrontRepositoryInterface $storefront)
    {
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
            $store = $this->storefront->resolveActiveStore();
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
            $store = $this->storefront->resolveActiveStore();

            if (!$store) {
                return response()->json([
                    'success' => true,
                    'store' => null,
                    'categories' => [],
                ]);
            }

            $categories = $this->storefront->activeCategories((int) $store->id);
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

    public function productDetail(string $slug): JsonResponse
    {
        try {
            $store = $this->storefront->resolveActiveStore();

            if (!$store) {
                return response()->json(['success' => false, 'message' => 'Store not found.'], 404);
            }

            $product = $this->storefront->findProductBySlug($store, $slug);
        } catch (QueryException) {
            return response()->json(['success' => false, 'message' => 'Server error.'], 500);
        }

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function products(StorefrontProductsRequest $request): JsonResponse
    {
        try {
            $store = $this->storefront->resolveActiveStore();

            if (!$store) {
                return response()->json([
                    'success' => true,
                    'store' => null,
                    'products' => [],
                ]);
            }

            $categories = $this->storefront->activeCategoryReferences((int) $store->id);
            $categoryIds = $request->categoryIds();

            if ($categoryIds) {
                $categoryIds = $this->storefront->resolveCategoryDescendants($categories, $categoryIds);
            }

            $products = $this->storefront->activeProducts($store, $categoryIds);
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
}
