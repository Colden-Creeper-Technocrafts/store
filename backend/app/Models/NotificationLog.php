<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'order_id',
        'channel',
        'event',
        'recipient',
        'status',
        'error_message',
    ];
}
