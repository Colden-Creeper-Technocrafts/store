<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Interfaces\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $store = $this->products->resolveActiveStore();

            if (!$store) {
                return response()->json(['success' => true, 'products' => [], 'meta' => []]);
            }

            $paginated = $this->products->paginateForAdmin((int) $store->id, $request->query())->appends($request->query());
            
            $products = collect($paginated->items())->map(function ($product) {
                $defaultVariant = $product->defaultVariant;

                return array_merge(
                    $product->toArray(),
                    [
                        'category_name' => $product->category?->name,
                        'sku' => $defaultVariant?->sku ?? $product->sku,
                        'price' => $defaultVariant?->price ?? $product->price,
                        'quantity' => $defaultVariant?->quantity ?? $product->quantity,
                        'status' => $defaultVariant?->status ?? $product->status,
                    ]
                );
            })->all();
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'products' => [], 'meta' => []], 500);
        }

        return response()->json([
            'success' => true,
            'products' => $products,
            'meta' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $store = $this->products->resolveActiveStore();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Store not found'], 404);
        }

        $product = $this->products->findForStore((int) $store->id, $id);

        if (!$product) {
            return response()->json(['success' => false, 'product' => null], 404);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $store = $this->products->resolveActiveStore();

            if (!$store) {
                return response()->json(['success' => false, 'message' => 'Store not found'], 404);
            }

            $product = $this->products->createForStore((int) $store->id, $request->validated());
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'product' => null], 500);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $store = $this->products->resolveActiveStore();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Store not found'], 404);
        }

        $product = $this->products->findForStore((int) $store->id, $id);

        if (!$product) {
            return response()->json(['success' => false, 'product' => null], 404);
        }

        try {
            $product = $this->products->update($product, $request->validated());
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'product' => null], 500);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function destroy(int $id): JsonResponse
    {
        $store = $this->products->resolveActiveStore();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Store not found'], 404);
        }

        $product = $this->products->findForStore((int) $store->id, $id);

        if (!$product) {
            return response()->json(['success' => false], 404);
        }

        try {
            $this->products->delete($product);
        } catch (QueryException $e) {
            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true]);
    }
}
