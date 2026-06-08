<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZoneLocation extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'type',
        'value',
    ];

    public const TYPES = ['state', 'pincode_prefix', 'pincode'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
