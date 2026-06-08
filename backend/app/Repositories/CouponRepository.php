<?php

namespace App\Repositories;

use App\Interfaces\CouponRepositoryInterface;
use App\Models\Coupon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponRepository implements CouponRepositoryInterface
{
    public function all(): Collection
    {
        return Coupon::orderByDesc('created_at')->get();
    }

    public function find(int $id): ?Coupon
    {
        return Coupon::find($id);
    }

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::where('code', strtoupper($code))->first();
    }

    public function create(array $data): Coupon
    {
        $data['code'] = strtoupper($data['code']);
        return Coupon::create($data);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        $coupon->update($data);
        return $coupon->fresh();
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    public function validate(Coupon $coupon, float $subtotal): void
    {
        if (!$coupon->is_active) {
            throw ValidationException::withMessages(['coupon_code' => ['This coupon is not active.']]);
        }

        if (!$coupon->isStarted()) {
            throw ValidationException::withMessages(['coupon_code' => ['This coupon is not yet valid.']]);
        }

        if ($coupon->isExpired()) {
            throw ValidationException::withMessages(['coupon_code' => ['This coupon has expired.']]);
        }

        if (!$coupon->hasUsageLeft()) {
            throw ValidationException::withMessages(['coupon_code' => ['This coupon has reached its usage limit.']]);
        }

        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => [sprintf(
                    'This coupon requires a minimum order of %.2f.',
                    $coupon->min_order_amount
                )],
            ]);
        }
    }

    public function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->discount_type === 'percentage') {
            $discount = $subtotal * ($coupon->discount_value / 100);
            if ($coupon->max_discount_amount !== null) {
                $discount = min($discount, (float) $coupon->max_discount_amount);
            }
        } else {
            $discount = (float) $coupon->discount_value;
        }

        return round(min($discount, $subtotal), 2);
    }
}
