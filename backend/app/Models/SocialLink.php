<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = ['name', 'url', 'icon', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];
}
