<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'credentials',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'credentials' => 'encrypted:array',
        'settings'    => 'array',
        'sort_order'  => 'integer',
    ];

    protected $hidden = ['credentials'];

    public const SLUGS = ['shiprocket', 'delhivery', 'manual'];

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
