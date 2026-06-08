<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestCheckoutRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Interfaces\CartRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    public function guestCheckout(GuestCheckoutRequest $request): JsonResponse
    {
        $shippingData = collect($request->validated())->except('items')->all();
        $order        = $this->orders->createFromGuestItems($request->validated('items'), $shippingData);

        return response()->json(['success' => true, 'order' => $order], 201);
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cart   = $this->carts->getOrCreateForUser($userId);
        $cart   = $this->carts->loadWithItems($cart);

        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        $order = $this->orders->createFromCart($userId, $cart, $request->validated());

        return response()->json(['success' => true, 'order' => $order], 201);
    }
}
