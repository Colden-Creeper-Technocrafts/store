<?php

namespace App\Repositories;

use App\Interfaces\ProductVariantRepositoryInterface;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function findProduct(int $productId): ?Product
    {
        return Product::find($productId);
    }

    public function allForProduct(Product $product): Collection
    {
        return $product->variants()->orderBy('id')->get();
    }

    public function findForProduct(Product $product, int $id): ?ProductVariant
    {
        return $product->variants()->find($id);
    }

    public function create(Product $product, array $payload): ProductVariant
    {
        return DB::transaction(function () use ($product, $payload) {
            // First variant for a product is always default regardless of what the form sends
            $isFirstVariant = $product->variants()->doesntExist();
            $isDefault = $isFirstVariant || !empty($payload['is_default']);

            if ($isDefault) {
                $product->variants()->where('is_default', true)->update(['is_default' => false]);
            }

            $variant = $product->variants()->create([
                'sku'        => $payload['sku'] ?? null,
                'price'      => $payload['price'] ?? 0,
                'sale_price' => $payload['sale_price'] ?? null,
                'quantity'   => $payload['quantity'] ?? 0,
                'weight'     => $payload['weight'] ?? null,
                'status'     => $payload['status'] ?? true,
                'options'    => $payload['options'] ?? null,
                'is_default' => $isDefault,
            ]);

            if ($variant->is_default) {
                $this->syncProductFromVariant($product, $variant);
            }

            return $variant;
        });
    }

    public function update(Product $product, ProductVariant $variant, array $payload): ProductVariant
    {
        return DB::transaction(function () use ($product, $variant, $payload) {
            if (!empty($payload['is_default'])) {
                $product->variants()->where('is_default', true)->update(['is_default' => false]);
            }

            $variant->update([
                'sku' => $payload['sku'] ?? $variant->sku,
                'price' => $payload['price'] ?? $variant->price,
                'sale_price' => array_key_exists('sale_price', $payload) ? $payload['sale_price'] : $variant->sale_price,
                'quantity' => $payload['quantity'] ?? $variant->quantity,
                'weight' => $payload['weight'] ?? $variant->weight,
                'status' => array_key_exists('status', $payload) ? $payload['status'] : $variant->status,
                'options' => array_key_exists('options', $payload) ? $payload['options'] : $variant->options,
                'is_default' => $payload['is_default'] ?? $variant->is_default,
            ]);

            if ($variant->is_default) {
                $this->syncProductFromVariant($product, $variant);
            }

            return $variant;
        });
    }

    public function delete(Product $product, ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($product, $variant) {
            $wasDefault = $variant->is_default;
            $deleted = $variant->delete();

            if ($wasDefault) {
                $fallback = $product->variants()->first();

                if ($fallback) {
                    $fallback->update(['is_default' => true]);
                    $this->syncProductFromVariant($product, $fallback);
                } else {
                    $product->quantity = 0;
                    $product->saveQuietly();
                }
            }

            return $deleted;
        });
    }

    private function syncProductFromVariant(Product $product, ProductVariant $variant): void
    {
        $product->sku = $variant->sku ?: $product->sku;
        $product->price = $variant->price;
        $product->quantity = $variant->quantity;
        $product->status = $variant->status;
        $product->saveQuietly();
    }
}
