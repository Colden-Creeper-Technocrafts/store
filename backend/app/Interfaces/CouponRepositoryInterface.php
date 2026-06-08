<?php

namespace App\Interfaces;

use App\Models\Coupon;
use Illuminate\Support\Collection;

interface CouponRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Coupon;

    public function findByCode(string $code): ?Coupon;

    public function create(array $data): Coupon;

    public function update(Coupon $coupon, array $data): Coupon;

    public function delete(Coupon $coupon): void;

    /** Throws ValidationException if the coupon cannot be applied. */
    public function validate(Coupon $coupon, float $subtotal): void;

    /** Returns the discount amount (never exceeds subtotal). */
    public function calculateDiscount(Coupon $coupon, float $subtotal): float;
}
