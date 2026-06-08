<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(ShippingZoneLocation::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}
