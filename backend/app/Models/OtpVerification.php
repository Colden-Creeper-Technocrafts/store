<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $phone
 * @property string|null $email
 * @property string $otp
 * @property string|null $email_token
 * @property int|null $order_id
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon|null $email_verified_at
 */
class OtpVerification extends Model
{
    protected $fillable = [
        'phone', 'email', 'otp', 'email_token',
        'order_id', 'expires_at', 'verified_at', 'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'        => 'datetime',
            'verified_at'       => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
