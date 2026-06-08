<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'shipping_zone_id',
        'min_weight_kg',
        'max_weight_kg',
        'min_order_amount',
        'max_order_amount',
        'base_rate',
        'per_kg_rate',
        'is_free',
        'sort_order',
    ];

    protected $casts = [
        'min_weight_kg'    => 'decimal:3',
        'max_weight_kg'    => 'decimal:3',
        'min_order_amount' => 'decimal:2',
        'max_order_amount' => 'decimal:2',
        'base_rate'        => 'decimal:2',
        'per_kg_rate'      => 'decimal:2',
        'is_free'          => 'boolean',
        'sort_order'       => 'integer',
    ];

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id')->withDefault();
    }
}
