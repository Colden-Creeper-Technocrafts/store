<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = $perPage > 0 ? min(100, $perPage) : 20;

            $query = Product::with(['category', 'defaultVariant']);

            if ($request->filled('category_id')) {
                $query->where('category_id', (int) $request->query('category_id'));
            }

            if ($request->has('status')) {
                $status = $request->query('status');
                if ($status === '1' || $status === '0' || is_bool($status)) {
                    $query->where('status', (bool) $status);
                }
            }

            if ($request->filled('search')) {
                $search = (string) $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            }

            $paginated = $query->orderBy('name')->paginate($perPage)->appends($request->query());
            
            $products = collect($paginated->items())->map(function ($product) {
                $defaultVariant = $product->defaultVariant;

                return array_merge(
                    $product->toArray(),
                    [
                        'category_name' => $product->category?->name,
                        'sku' => $defaultVariant->sku ?? $product->sku,
                        'price' => $defaultVariant->price ?? $product->price,
                        'quantity' => $defaultVariant->quantity ?? $product->quantity,
                        'status' => $defaultVariant->status ?? $product->status,
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

    public function show($id): JsonResponse
    {
        $product = Product::with('defaultVariant')->find($id);

        if (!$product) {
            return response()->json(['success' => false, 'product' => null], 404);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'integer'],
            'weight' => ['nullable', 'numeric'],
            'status' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $payload['sku'] = $payload['sku'] ?? $payload['slug'];

        try {
            $product = Product::create($payload + ['status' => $payload['status'] ?? true, 'featured' => $payload['featured'] ?? false]);

            $variantPayload = [
                'sku' => $payload['sku'],
                'price' => $payload['price'] ?? 0,
                'sale_price' => $payload['sale_price'] ?? null,
                'quantity' => $payload['quantity'] ?? 0,
                'weight' => $payload['weight'] ?? null,
                'status' => $payload['status'] ?? true,
                'is_default' => true,
            ];

            $product->variants()->create($variantPayload);
            $product->quantity = $variantPayload['quantity'];
            $product->saveQuietly();
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'product' => null], 500);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false, 'product' => null], 404);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product->id)],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product->id)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'numeric'],
            'quantity' => ['nullable', 'integer'],
            'weight' => ['nullable', 'numeric'],
            'status' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        if (empty($payload['sku'])) {
            $payload['sku'] = $product->sku ?? $payload['slug'];
        }

        try {
            $product->update($payload);

            $variantPayload = [
                'sku' => $payload['sku'],
                'price' => $payload['price'] ?? 0,
                'sale_price' => $payload['sale_price'] ?? null,
                'quantity' => $payload['quantity'] ?? 0,
                'weight' => $payload['weight'] ?? null,
                'status' => $payload['status'] ?? $product->status,
                'is_default' => true,
            ];

            $defaultVariant = $product->defaultVariant()->first();
            if ($defaultVariant) {
                $defaultVariant->update($variantPayload);
            } else {
                $product->variants()->create($variantPayload);
            }

            $product->quantity = $variantPayload['quantity'];
            $product->saveQuietly();
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'product' => null], 500);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function destroy($id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['success' => false], 404);
        }

        try {
            $product->delete();
        } catch (QueryException $e) {
            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true]);
    }
}
