<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'payment_status',
        'payment_gateway',
        'payment_id',
        'razorpay_order_id',
        'subtotal',
        'discount_amount',
        'total',
        'shipping_cost',
        'shipping_method_id',
        'coupon_id',
        'coupon_code',
        'shipping_name',
        'shipping_email',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'notes',
        'admin_notes',
        'tracking_number',
        'tracking_url',
        'return_status',
        'return_reason',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total'           => 'decimal:2',
        'shipping_cost'   => 'decimal:2',
    ];

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];
    public const RETURN_STATUSES = ['requested', 'approved', 'rejected', 'refunded'];

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

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class)->withDefault();
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
