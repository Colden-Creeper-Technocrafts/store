<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Interfaces\ProductVariantRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function __construct(private readonly ProductVariantRepositoryInterface $variants)
    {
    }

    public function index(int $productId): JsonResponse
    {
        $product = $this->variants->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'variants' => []], 404);
        }

        $variants = $this->variants->allForProduct($product);

        return response()->json(['success' => true, 'variants' => $variants]);
    }

    public function show(int $productId, int $id): JsonResponse
    {
        $product = $this->variants->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'variant' => null], 404);
        }

        $variant = $this->variants->findForProduct($product, $id);

        if (!$variant) {
            return response()->json(['success' => false, 'variant' => null], 404);
        }

        return response()->json(['success' => true, 'variant' => $variant]);
    }

    public function store(StoreProductVariantRequest $request, int $productId): JsonResponse
    {
        $product = $this->variants->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'variant' => null], 404);
        }

        try {
            $variant = $this->variants->create($product, $request->validated());
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'variant' => null], 500);
        }

        return response()->json(['success' => true, 'variant' => $variant]);
    }

    public function update(UpdateProductVariantRequest $request, int $productId, int $id): JsonResponse
    {
        $product = $this->variants->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'variant' => null], 404);
        }

        $variant = $this->variants->findForProduct($product, $id);

        if (!$variant) {
            return response()->json(['success' => false, 'variant' => null], 404);
        }

        try {
            $variant = $this->variants->update($product, $variant, $request->validated());
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'variant' => null], 500);
        }

        return response()->json(['success' => true, 'variant' => $variant]);
    }

    public function destroy(int $productId, int $id): JsonResponse
    {
        $product = $this->variants->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false], 404);
        }

        $variant = $this->variants->findForProduct($product, $id);

        if (!$variant) {
            return response()->json(['success' => false], 404);
        }

        try {
            $this->variants->delete($product, $variant);
        } catch (QueryException $e) {
            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true]);
    }
}
