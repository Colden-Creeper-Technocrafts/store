<?php

namespace App\Shipping\DTOs;

readonly class ShipmentResponse
{
    public function __construct(
        public bool    $success,
        public ?string $awbNumber          = null,
        public ?string $providerShipmentId = null,
        public ?string $labelUrl           = null,
        public ?string $trackingUrl        = null,
        public ?string $errorMessage       = null,
        public array   $meta               = [],
    ) {}

    public static function failure(string $message, array $meta = []): self
    {
        return new self(success: false, errorMessage: $message, meta: $meta);
    }
}
