<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Interfaces\CartRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartRepositoryInterface $carts)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->carts->getOrCreateForUser($request->user()->id);
        $cart = $this->carts->loadWithItems($cart);

        return response()->json(['success' => true, 'cart' => $this->formatCart($cart)]);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->carts->getOrCreateForUser($request->user()->id);
        $this->carts->addItem($cart, $request->validated('product_id'), $request->validated('quantity'));
        $cart = $this->carts->loadWithItems($cart);

        return response()->json(['success' => true, 'cart' => $this->formatCart($cart)]);
    }

    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->getOrCreateForUser($request->user()->id);
        $item = $this->carts->findItemById($cart, $itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $this->carts->updateItemQty($item, $request->validated('quantity'));
        $cart = $this->carts->loadWithItems($cart);

        return response()->json(['success' => true, 'cart' => $this->formatCart($cart)]);
    }

    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->carts->getOrCreateForUser($request->user()->id);
        $item = $this->carts->findItemById($cart, $itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $this->carts->removeItem($item);
        $cart = $this->carts->loadWithItems($cart);

        return response()->json(['success' => true, 'cart' => $this->formatCart($cart)]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->carts->getOrCreateForUser($request->user()->id);
        $this->carts->clear($cart);

        return response()->json(['success' => true, 'cart' => $this->formatCart($cart->fresh())]);
    }

    private function formatCart(\App\Models\Cart $cart): array
    {
        $items = ($cart->relationLoaded('items') ? $cart->items : collect())->map(function ($item) {
            $product   = $item->product;
            $image     = $product->primaryImage ?? null;
            $imageUrl  = $image
                ? asset('storage/' . ltrim($image->image, '/'))
                : asset('images/product-placeholder.svg');
            $price     = (float) ($product->sale_price ?? $product->price);

            return [
                'id'         => $item->id,
                'product_id' => $product->id,
                'quantity'   => $item->quantity,
                'line_total' => round($price * $item->quantity, 2),
                'product'    => [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'sku'         => $product->sku,
                    'price'       => (float) $product->price,
                    'sale_price'  => $product->sale_price ? (float) $product->sale_price : null,
                    'image_url'   => $imageUrl,
                ],
            ];
        })->values()->all();

        $subtotal = array_sum(array_column($items, 'line_total'));

        return [
            'id'         => $cart->id,
            'items'      => $items,
            'item_count' => array_sum(array_column($items, 'quantity')),
            'subtotal'   => round($subtotal, 2),
            'total'      => round($subtotal, 2),
        ];
    }
}
