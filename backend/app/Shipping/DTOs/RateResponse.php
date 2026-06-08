<?php

namespace App\Shipping\DTOs;

readonly class RateResponse
{
    public function __construct(
        public string $methodCode,
        public string $methodName,
        public string $providerSlug,
        public float  $cost,
        public int    $minDays,
        public int    $maxDays,
        public bool   $isFree = false,
        public ?int   $methodId = null,
    ) {}

    public function deliveryEstimate(): string
    {
        if ($this->minDays === $this->maxDays) {
            return "{$this->minDays} day(s)";
        }

        return "{$this->minDays}–{$this->maxDays} days";
    }
}
