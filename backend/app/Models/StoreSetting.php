<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'business_type',
        'store_email',
        'store_phone',
        'store_description',
        'currency',
        'features',
        'layout',
        'is_active',
    ];

    protected $casts = [
        'features'  => 'array',
        'is_active' => 'boolean',
    ];

    public static function active(): ?self
    {
        return self::where('is_active', true)->orderBy('id')->first()
            ?? self::orderByDesc('is_active')->orderBy('id')->first();
    }
}
