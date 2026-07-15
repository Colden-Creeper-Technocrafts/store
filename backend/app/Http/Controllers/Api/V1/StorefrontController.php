<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorefrontProductsRequest;
use App\Interfaces\StorefrontRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    private function buildCategoryTree(Collection $categories, Request $request, ?int $parentId = null): array
    {
        return $categories
            ->where('parent_category_id', $parentId)
            ->sortBy('sort_order')
            ->values()
            ->map(function (object $category) use ($categories, $request): array {
                return [
                    'id'          => $category->id,
                    'name'        => $category->name,
                    'slug'        => $category->slug,
                    'description' => $category->description,
                    'image'       => $this->resolveLogoUrl($category->image ?? null, $request),
                    'children'    => $this->buildCategoryTree($categories, $request, $category->id),
                ];
            })
            ->all();
    }

    private function resolveLogoUrl(?string $path, Request $request): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return rtrim($request->root(), '/') . Storage::url($path);
    }

    public function show(Request $request): JsonResponse
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
                'name'         => (string) ($store->store_name ?? 'Kumkum Novelty Store'),
                'layout'       => $layout,
                'logo_url'     => $this->resolveLogoUrl($store->logo_url ?? null, $request),
                'favicon_url'  => $this->resolveLogoUrl($store->favicon_url ?? null, $request),
                'tagline'      => $store->tagline ?? null,
                'banner_image' => $this->resolveLogoUrl($store->banner_image ?? null, $request),
                'banner_title' => $store->banner_title ?? null,
                'banner_text'  => $store->banner_text ?? null,
                'currency'              => $store->currency ?? 'INR',
                'shipping_charge'       => (float) ($store->shipping_charge ?? 0),
                'free_shipping_threshold' => isset($store->free_shipping_threshold) ? (float) $store->free_shipping_threshold : null,
            ],
        ]);
    }

    public function categories(Request $request): JsonResponse
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
            'categories' => $this->buildCategoryTree($categories, $request),
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

    public function coupons(): JsonResponse
    {
        $now = now();

        $coupons = \DB::table('coupons')
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->orderBy('starts_at')
            ->get(['code', 'description', 'discount_type', 'discount_value', 'min_order_amount', 'starts_at', 'expires_at']);

        $live = $coupons->filter(fn($c) => is_null($c->expires_at) || $c->expires_at > $now)->values();
        $upcoming = \DB::table('coupons')
            ->where('is_active', true)
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->get(['code', 'description', 'discount_type', 'discount_value', 'min_order_amount', 'starts_at', 'expires_at'])
            ->values();

        return response()->json([
            'success'  => true,
            'live'     => $live,
            'upcoming' => $upcoming,
        ]);
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
