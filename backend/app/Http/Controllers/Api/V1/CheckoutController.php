<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestCheckoutRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Interfaces\CartRepositoryInterface;
use App\Interfaces\CouponRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly OrderRepositoryInterface $orders,
        private readonly CouponRepositoryInterface $coupons,
    ) {
    }

    public function guestCheckout(GuestCheckoutRequest $request): JsonResponse
    {
        $validated    = $request->validated();
        $coupon       = null;

        if (!empty($validated['coupon_code'])) {
            $coupon = $this->coupons->findByCode($validated['coupon_code']);
            if (!$coupon) {
                throw ValidationException::withMessages(['coupon_code' => ['Invalid coupon code.']]);
            }
        }

        $shippingData = collect($validated)->except(['items', 'coupon_code'])->all();
        $order        = $this->orders->createFromGuestItems($validated['items'], $shippingData, $coupon);

        return response()->json(['success' => true, 'order' => $order], 201);
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId    = $request->user()->id;
        $coupon    = null;

        if (!empty($validated['coupon_code'])) {
            $coupon = $this->coupons->findByCode($validated['coupon_code']);
            if (!$coupon) {
                throw ValidationException::withMessages(['coupon_code' => ['Invalid coupon code.']]);
            }
        }

        $cart = $this->carts->getOrCreateForUser($userId);
        $cart = $this->carts->loadWithItems($cart);

        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        $shippingData = collect($validated)->except('coupon_code')->all();
        $order        = $this->orders->createFromCart($userId, $cart, $shippingData, $coupon);

        return response()->json(['success' => true, 'order' => $order], 201);
    }
}
