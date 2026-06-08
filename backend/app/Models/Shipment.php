<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'shipping_provider_id',
        'shipping_method_id',
        'awb_number',
        'tracking_url',
        'provider_shipment_id',
        'label_url',
        'status',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'estimated_delivery_date',
        'shipped_at',
        'delivered_at',
        'meta',
    ];

    protected $casts = [
        'weight_kg'               => 'decimal:3',
        'length_cm'               => 'decimal:2',
        'width_cm'                => 'decimal:2',
        'height_cm'               => 'decimal:2',
        'estimated_delivery_date' => 'date',
        'shipped_at'              => 'datetime',
        'delivered_at'            => 'datetime',
        'meta'                    => 'array',
    ];

    public const STATUSES = [
        'pending',
        'booked',
        'ready_to_ship',
        'picked_up',
        'in_transit',
        'out_for_delivery',
        'delivered',
        'failed',
        'cancelled',
        'rto_initiated',
        'rto_delivered',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ShippingProvider::class, 'shipping_provider_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id')->withDefault();
    }
}
