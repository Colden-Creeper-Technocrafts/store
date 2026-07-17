<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;

class AdminCartController extends Controller
{
    public function index(): JsonResponse
    {
        $carts = Cart::with(['user:id,name,email', 'items.product'])
            ->has('items')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Cart $cart) {
                $subtotal = $cart->items->sum(function ($item) {
                    $price = (float) ($item->product->sale_price ?? $item->product->price ?? 0);
                    return round($price * $item->quantity, 2);
                });

                return [
                    'id'           => $cart->id,
                    'user'         => [
                        'id'    => $cart->user?->id,
                        'name'  => $cart->user?->name ?? 'Unknown',
                        'email' => $cart->user?->email ?? '—',
                    ],
                    'item_count'   => $cart->items->sum('quantity'),
                    'subtotal'     => round($subtotal, 2),
                    'updated_at'   => $cart->updated_at?->toISOString(),
                    'items'        => $cart->items->map(fn($item) => [
                        'id'           => $item->id,
                        'product_name' => $item->product?->name ?? 'Unknown product',
                        'quantity'     => $item->quantity,
                        'price'        => (float) ($item->product?->sale_price ?? $item->product?->price ?? 0),
                    ])->values()->all(),
                ];
            });

        return response()->json(['success' => true, 'carts' => $carts]);
    }

    public function destroy(int $id): JsonResponse
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Cart not found'], 404);
        }

        $cart->items()->delete();

        return response()->json(['success' => true]);
    }
}
