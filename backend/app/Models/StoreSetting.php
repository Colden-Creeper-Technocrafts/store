<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string      $store_name
 * @property string|null $business_type
 * @property string|null $store_email
 * @property string|null $store_phone
 * @property string|null $store_description
 * @property string|null $logo_url
 * @property string|null $favicon_url
 * @property string|null $tagline
 * @property string|null $banner_image
 * @property string|null $banner_title
 * @property string|null $banner_text
 * @property string|null $currency
 * @property array|null  $features
 * @property array|null  $notification_config
 * @property string|null $layout
 * @property bool        $is_active
 */
class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'business_type',
        'store_email',
        'store_phone',
        'store_description',
        'logo_url',
        'favicon_url',
        'tagline',
        'banner_image',
        'banner_title',
        'banner_text',
        'currency',
        'shipping_charge',
        'free_shipping_threshold',
        'features',
        'notification_config',
        'layout',
        'is_active',
    ];

    protected $casts = [
        'shipping_charge'          => 'float',
        'free_shipping_threshold'  => 'float',
        'features'                 => 'array',
        'notification_config'      => 'array',
        'is_active'                => 'boolean',
    ];

    public static function active(): ?self
    {
        return self::where('is_active', true)->orderBy('id')->first()
            ?? self::orderByDesc('is_active')->orderBy('id')->first();
    }
}
