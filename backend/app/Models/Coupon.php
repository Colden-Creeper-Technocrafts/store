<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value'     => 'float',
        'min_order_amount'   => 'float',
        'max_discount_amount'=> 'float',
        'usage_limit'        => 'integer',
        'used_count'         => 'integer',
        'is_active'          => 'boolean',
        'starts_at'          => 'datetime',
        'expires_at'         => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isStarted(): bool
    {
        return $this->starts_at === null || !$this->starts_at->isFuture();
    }

    public function hasUsageLeft(): bool
    {
        return $this->usage_limit === null || $this->used_count < $this->usage_limit;
    }
}
