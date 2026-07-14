<?php

namespace App\Repositories;

use App\Interfaces\ProductImageRepositoryInterface;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageRepository implements ProductImageRepositoryInterface
{
    public function findProduct(int $productId): ?Product
    {
        return Product::find($productId);
    }

    public function allForProduct(Product $product): Collection
    {
        return $product->images()->orderByDesc('is_primary')->orderBy('sort_order')->get();
    }

    public function findForProduct(Product $product, int $id): ?ProductImage
    {
        return $product->images()->where('id', $id)->first();
    }

    public function create(Product $product, UploadedFile $image, array $payload): ProductImage
    {
        return DB::transaction(function () use ($product, $image, $payload) {
            $path      = $image->store('products', 'public');
            $variantId = (int) $payload['variant_id'];
            $isPrimary = (bool) ($payload['is_primary'] ?? false);

            $hasImages = ProductImage::where('product_variant_id', $variantId)->exists();

            if ($isPrimary || !$hasImages) {
                ProductImage::where('product_variant_id', $variantId)->update(['is_primary' => false]);
                $isPrimary = true;
            }

            return $product->images()->create([
                'product_variant_id' => $variantId,
                'image'              => $path,
                'sort_order'         => $payload['sort_order'] ?? 0,
                'is_primary'         => $isPrimary,
            ]);
        });
    }

    public function update(Product $product, ProductImage $image, array $payload): ProductImage
    {
        return DB::transaction(function () use ($product, $image, $payload) {
            if (!empty($payload['is_primary'])) {
                // Scope to the same variant so other variants' primary is untouched
                ProductImage::where('product_variant_id', $image->product_variant_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }

            $image->update([
                'sort_order' => $payload['sort_order'] ?? $image->sort_order,
                'is_primary' => array_key_exists('is_primary', $payload) ? (bool) $payload['is_primary'] : $image->is_primary,
            ]);

            return $image;
        });
    }

    public function delete(Product $product, ProductImage $image): bool
    {
        return DB::transaction(function () use ($image) {
            $wasPrimary = $image->is_primary;
            $variantId  = $image->product_variant_id;
            $path       = $image->image;
            $deleted    = $image->delete();

            Storage::disk('public')->delete($path);

            if ($wasPrimary && $variantId) {
                $fallback = ProductImage::where('product_variant_id', $variantId)->first();
                if ($fallback) {
                    $fallback->update(['is_primary' => true]);
                }
            }

            return $deleted;
        });
    }
}
