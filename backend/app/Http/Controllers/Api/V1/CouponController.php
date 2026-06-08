<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Interfaces\CouponRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    // ── Admin ────────────────────────────────────────────────────────────────

    public function index(): JsonResponse
    {
        return response()->json(['coupons' => $this->coupons->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'               => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'description'        => ['nullable', 'string', 'max:500'],
            'discount_type'      => ['required', 'in:percentage,fixed'],
            'discount_value'     => ['required', 'numeric', 'min:0.01'],
            'min_order_amount'   => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount'=> ['nullable', 'numeric', 'min:0'],
            'usage_limit'        => ['nullable', 'integer', 'min:1'],
            'starts_at'          => ['nullable', 'date'],
            'expires_at'         => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'          => ['boolean'],
        ]);

        $coupon = $this->coupons->create($data);

        return response()->json(['coupon' => $coupon], 201);
    }

    public function show(int $id): JsonResponse
    {
        $coupon = $this->coupons->find($id);
        if (!$coupon) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(['coupon' => $coupon]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $coupon = $this->coupons->find($id);
        if (!$coupon) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'code'               => ['sometimes', 'string', 'max:64', "unique:coupons,code,{$id}"],
            'description'        => ['nullable', 'string', 'max:500'],
            'discount_type'      => ['sometimes', 'in:percentage,fixed'],
            'discount_value'     => ['sometimes', 'numeric', 'min:0.01'],
            'min_order_amount'   => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount'=> ['nullable', 'numeric', 'min:0'],
            'usage_limit'        => ['nullable', 'integer', 'min:1'],
            'starts_at'          => ['nullable', 'date'],
            'expires_at'         => ['nullable', 'date'],
            'is_active'          => ['boolean'],
        ]);

        $coupon = $this->coupons->update($coupon, $data);

        return response()->json(['coupon' => $coupon]);
    }

    public function destroy(int $id): JsonResponse
    {
        $coupon = $this->coupons->find($id);
        if (!$coupon) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->coupons->delete($coupon);
        return response()->json(['success' => true]);
    }

    // ── Customer ─────────────────────────────────────────────────────────────

    /** Validate a coupon code against a given subtotal; returns discount preview. */
    public function validateCode(Request $request): JsonResponse
    {
        $data   = $request->validate([
            'code'     => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = $this->coupons->findByCode($data['code']);

        if (!$coupon) {
            throw ValidationException::withMessages(['code' => ['Invalid coupon code.']]);
        }

        $this->coupons->validate($coupon, (float) $data['subtotal']);

        $discount = $this->coupons->calculateDiscount($coupon, (float) $data['subtotal']);

        return response()->json([
            'valid'           => true,
            'code'            => $coupon->code,
            'discount_type'   => $coupon->discount_type,
            'discount_value'  => $coupon->discount_value,
            'discount_amount' => $discount,
            'description'     => $coupon->description,
        ]);
    }
}
