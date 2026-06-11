<?php

namespace App\Repositories;

use App\Interfaces\CartRepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartRepository implements CartRepositoryInterface
{
    public function getOrCreateForUser(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    public function loadWithItems(Cart $cart): Cart
    {
        return $cart->load(['items.product.primaryImage']);
    }

    public function findItem(Cart $cart, int $productId): ?CartItem
    {
        return $cart->items()->where('product_id', $productId)->first();
    }

    public function findItemById(Cart $cart, int $itemId): ?CartItem
    {
        return $cart->items()->where('id', $itemId)->first();
    }

    public function addItem(Cart $cart, int $productId, int $quantity): CartItem
    {
        $product  = Product::findOrFail($productId);
        $existing = $this->findItem($cart, $productId);
        $newTotal = ($existing ? $existing->quantity : 0) + $quantity;

        $this->assertStock($product, $newTotal);

        if ($existing) {
            $existing->increment('quantity', $quantity);
            return $existing->fresh();
        }

        return $cart->items()->create([
            'product_id' => $productId,
            'quantity'   => $quantity,
        ]);
    }

    public function updateItemQty(CartItem $item, int $quantity): CartItem
    {
        $this->assertStock(Product::findOrFail($item->product_id), $quantity);
        $item->update(['quantity' => $quantity]);
        return $item;
    }

    private function assertStock(Product $product, int $requested): void
    {
        $variantQty = DB::table('product_variants')
            ->where('product_id', $product->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('quantity');

        $stock = (int) ($variantQty ?? $product->quantity);

        if ($stock <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ["\"{$product->name}\" is out of stock."],
            ]);
        }
        if ($requested > $stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$stock} unit(s) of \"{$product->name}\" available."],
            ]);
        }
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
