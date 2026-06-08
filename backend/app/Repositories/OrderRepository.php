<?php

namespace App\Repositories;

use App\Interfaces\OrderRepositoryInterface;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderRepository implements OrderRepositoryInterface
{
    public function createFromCart(int $userId, Cart $cart, array $shippingData): Order
    {
        return DB::transaction(function () use ($userId, $cart, $shippingData) {
            $cart->load('items');

            $productIds     = $cart->items->pluck('product_id')->all();
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $this->assertStock($lockedProducts->get($item->product_id), $item->quantity);
            }

            $subtotal = 0;
            foreach ($cart->items as $item) {
                $p         = $lockedProducts->get($item->product_id);
                $subtotal += (float) ($p->sale_price ?? $p->price) * $item->quantity;
            }

            $order = Order::create(array_merge($shippingData, [
                'user_id'  => $userId,
                'status'   => 'pending',
                'subtotal' => round($subtotal, 2),
                'total'    => round($subtotal, 2),
            ]));

            foreach ($cart->items as $item) {
                $product = $lockedProducts->get($item->product_id);
                $price   = (float) ($product->sale_price ?? $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'sku'        => $product->sku,
                    'price'      => $price,
                    'quantity'   => $item->quantity,
                    'subtotal'   => round($price * $item->quantity, 2),
                ]);

                $product->decrement('quantity', $item->quantity);
            }

            $cart->items()->delete();

            return $order->load('items');
        });
    }

    public function createFromGuestItems(array $items, array $shippingData): Order
    {
        return DB::transaction(function () use ($items, $shippingData) {
            $productIds     = array_column($items, 'product_id');
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $this->assertStock($lockedProducts->get($item['product_id']), $item['quantity']);
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $p         = $lockedProducts->get($item['product_id']);
                $subtotal += (float) ($p->sale_price ?? $p->price) * $item['quantity'];
            }

            $order = Order::create(array_merge($shippingData, [
                'user_id'  => null,
                'status'   => 'pending',
                'subtotal' => round($subtotal, 2),
                'total'    => round($subtotal, 2),
            ]));

            foreach ($items as $item) {
                $product = $lockedProducts->get($item['product_id']);
                $price   = (float) ($product->sale_price ?? $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'sku'        => $product->sku,
                    'price'      => $price,
                    'quantity'   => $item['quantity'],
                    'subtotal'   => round($price * $item['quantity'], 2),
                ]);

                $product->decrement('quantity', $item['quantity']);
            }

            return $order->load('items');
        });
    }

    private function assertStock(?Product $product, int $requested): void
    {
        if (!$product) return;
        $stock = (int) $product->quantity;
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

    public function listForUser(int $userId): Collection
    {
        return Order::where('user_id', $userId)
            ->with('items')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findForUser(int $orderId, int $userId): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->with('items')
            ->first();
    }
}
