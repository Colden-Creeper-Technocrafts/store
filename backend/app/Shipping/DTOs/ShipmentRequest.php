<?php

namespace App\Shipping\DTOs;

readonly class ShipmentRequest
{
    /**
     * @param  array<int, array{name: string, sku: string|null, qty: int, price: float}>  $items
     */
    public function __construct(
        public int     $orderId,
        public string  $receiverName,
        public string  $receiverPhone,
        public string  $receiverEmail,
        public string  $receiverAddress,
        public string  $receiverCity,
        public string  $receiverState,
        public string  $receiverPincode,
        public string  $receiverCountry,
        public float   $weightKg,
        public float   $orderAmount,
        public array   $items,
        public string  $methodCode,
        public ?float  $lengthCm = null,
        public ?float  $widthCm  = null,
        public ?float  $heightCm = null,
        public ?string $notes    = null,
    ) {}
}
