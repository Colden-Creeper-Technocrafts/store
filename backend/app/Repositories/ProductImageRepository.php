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
        return $product->images()->get();
    }

    public function findForProduct(Product $product, int $id): ?ProductImage
    {
        return $product->images()->where('id', $id)->first();
    }

    public function create(Product $product, UploadedFile $image, array $payload): ProductImage
    {
        return DB::transaction(function () use ($product, $image, $payload) {
            $path = $image->store('products', 'public');
            $isPrimary = (bool) ($payload['is_primary'] ?? false);

            if ($isPrimary || !$product->images()->exists()) {
                $product->images()->update(['is_primary' => false]);
                $isPrimary = true;
            }

            return $product->images()->create([
                'image' => $path,
                'sort_order' => $payload['sort_order'] ?? 0,
                'is_primary' => $isPrimary,
            ]);
        });
    }

    public function update(Product $product, ProductImage $image, array $payload): ProductImage
    {
        return DB::transaction(function () use ($product, $image, $payload) {
            if (!empty($payload['is_primary'])) {
                $product->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);
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
        return DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;
            $path = $image->image;
            $deleted = $image->delete();

            Storage::disk('public')->delete($path);

            if ($wasPrimary) {
                $fallback = $product->images()->first();

                if ($fallback) {
                    $fallback->update(['is_primary' => true]);
                }
            }

            return $deleted;
        });
    }
}
