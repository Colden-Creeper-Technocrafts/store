<?php

namespace App\Repositories;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = $perPage > 0 ? min(100, $perPage) : 20;

        $query = Product::with(['category', 'defaultVariant']);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (array_key_exists('status', $filters)) {
            $status = $filters['status'];

            if ($status === '1' || $status === '0' || is_bool($status)) {
                $query->where('status', (bool) $status);
            }
        }

        if (!empty($filters['search'])) {
            $search = (string) $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findWithDefaultVariant(int $id): ?Product
    {
        return Product::with('defaultVariant')->find($id);
    }

    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    public function create(array $payload): Product
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
