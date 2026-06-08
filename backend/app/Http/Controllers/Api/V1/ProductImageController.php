<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Interfaces\ProductImageRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function __construct(private readonly ProductImageRepositoryInterface $images)
    {
    }

    public function defaults(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'default_image' => asset('images/product-placeholder.svg'),
        ]);
    }

    public function index(int $productId): JsonResponse
    {
        $product = $this->images->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'images' => []], 404);
        }

        return response()->json([
            'success' => true,
            'images' => $this->images->allForProduct($product),
        ]);
    }

    public function store(StoreProductImageRequest $request, int $productId): JsonResponse
    {
        $product = $this->images->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'image' => null], 404);
        }

        try {
            $image = $this->images->create($product, $request->file('image'), $request->validated());
        } catch (QueryException) {
            return response()->json(['success' => false, 'image' => null], 500);
        }

        return response()->json(['success' => true, 'image' => $image]);
    }

    public function update(UpdateProductImageRequest $request, int $productId, int $id): JsonResponse
    {
        $product = $this->images->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false, 'image' => null], 404);
        }

        $image = $this->images->findForProduct($product, $id);

        if (!$image) {
            return response()->json(['success' => false, 'image' => null], 404);
        }

        try {
            $image = $this->images->update($product, $image, $request->validated());
        } catch (QueryException) {
            return response()->json(['success' => false, 'image' => null], 500);
        }

        return response()->json(['success' => true, 'image' => $image]);
    }

    public function destroy(int $productId, int $id): JsonResponse
    {
        $product = $this->images->findProduct($productId);

        if (!$product) {
            return response()->json(['success' => false], 404);
        }

        $image = $this->images->findForProduct($product, $id);

        if (!$image) {
            return response()->json(['success' => false], 404);
        }

        try {
            $this->images->delete($product, $image);
        } catch (QueryException) {
            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true]);
    }
}
