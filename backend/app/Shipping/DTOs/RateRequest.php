<?php

namespace App\Shipping\DTOs;

readonly class RateRequest
{
    public function __construct(
        public float  $weightKg,
        public float  $orderAmount,
        public string $destinationPincode,
        public string $destinationState,
        public ?float $lengthCm = null,
        public ?float $widthCm  = null,
        public ?float $heightCm = null,
    ) {}
}
