<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'total',
        'coupon_id',
        'coupon_code',
        'shipping_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country',
        'notes',
        'admin_notes',
        'tracking_number',
        'tracking_url',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class)->withDefault();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }
}
