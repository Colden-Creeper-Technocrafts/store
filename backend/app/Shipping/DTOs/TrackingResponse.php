<?php

namespace App\Shipping\DTOs;

readonly class TrackingResponse
{
    /**
     * @param  TrackingInfo[]  $history
     */
    public function __construct(
        public bool    $success,
        public ?string $currentStatus    = null,
        public ?string $awbNumber        = null,
        public ?string $estimatedDelivery = null,
        public array   $history          = [],
        public ?string $errorMessage     = null,
    ) {}

    public static function failure(string $message): self
    {
        return new self(success: false, errorMessage: $message);
    }
}
