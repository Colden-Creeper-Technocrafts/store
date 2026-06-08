<?php

namespace App\Repositories;

use App\Interfaces\ProductRepositoryInterface;
use App\Interfaces\StorefrontRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly StorefrontRepositoryInterface $storefront)
    {
    }

    public function resolveActiveStore(): ?object
    {
        return $this->storefront->resolveActiveStore();
    }

    public function paginateForAdmin(int $storeId, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = $perPage > 0 ? min(100, $perPage) : 20;

        $query = Product::with(['category', 'defaultVariant'])
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where(function ($q) use ($storeId) {
                $q->where('categories.store_setting_id', $storeId)
                    ->orWhereNull('products.category_id');
            })
            ->select('products.*');

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', (int) $filters['category_id']);
        }

        if (array_key_exists('status', $filters)) {
            $status = $filters['status'];

            if ($status === '1' || $status === '0' || is_bool($status)) {
                $query->where('products.status', (bool) $status);
            }
        }

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.short_description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'out_of_stock') {
                $query->where('products.quantity', '<=', 0);
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->where('products.quantity', '>', 0)->where('products.quantity', '<=', 5);
            } elseif ($filters['stock_status'] === 'in_stock') {
                $query->where('products.quantity', '>', 5);
            }
        }

        return $query->orderBy('products.name')->paginate($perPage);
    }

    public function findForStore(int $storeId, int $id): ?Product
    {
        return Product::with(['category', 'defaultVariant', 'images'])
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('categories.store_setting_id', $storeId)
            ->where('products.id', $id)
            ->select('products.*')
            ->first();
    }

    public function createForStore(int $_storeId, array $payload): Product
    {
        return $this->create($payload);
    }

    private function create(array $payload): Product
    {
        return DB::transaction(function () use ($payload) {
            $product = Product::create($payload + [
                'status' => $payload['status'] ?? true,
                'featured' => $payload['featured'] ?? false,
            ]);

            $variantPayload = $this->defaultVariantPayload($payload, $payload['status'] ?? true);

            $product->variants()->create($variantPayload);
            $product->quantity = $variantPayload['quantity'];
            $product->saveQuietly();

            return $product;
        });
    }

    public function update(Product $product, array $payload): Product
    {
        return DB::transaction(function () use ($product, $payload) {
            if (empty($payload['sku'])) {
                $payload['sku'] = $product->sku ?? $payload['slug'];
            }

            $product->update($payload);

            $variantPayload = $this->defaultVariantPayload($payload, $payload['status'] ?? $product->status);
            $defaultVariant = $product->defaultVariant()->first();

            if ($defaultVariant) {
                $defaultVariant->update($variantPayload);
            } else {
                $product->variants()->create($variantPayload);
            }

            $product->quantity = $variantPayload['quantity'];
            $product->saveQuietly();

            return $product;
        });
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function adjustStock(Product $product, int $quantity): Product
    {
        return DB::transaction(function () use ($product, $quantity) {
            $product->update(['quantity' => $quantity]);

            $defaultVariant = $product->defaultVariant()->first();
            if ($defaultVariant) {
                $defaultVariant->update(['quantity' => $quantity]);
            }

            return $product->fresh(['defaultVariant']);
        });
    }

    private function defaultVariantPayload(array $payload, bool $status): array
    {
        return [
            'sku' => $payload['sku'],
            'price' => $payload['price'] ?? 0,
            'sale_price' => $payload['sale_price'] ?? null,
            'quantity' => $payload['quantity'] ?? 0,
            'weight' => $payload['weight'] ?? null,
            'status' => $status,
            'is_default' => true,
        ];
    }
}
