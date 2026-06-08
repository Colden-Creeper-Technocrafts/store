<?php

namespace App\Shipping\DTOs;

use DateTimeInterface;

readonly class TrackingInfo
{
    public function __construct(
        public string             $status,
        public ?string            $location    = null,
        public ?string            $description = null,
        public ?DateTimeInterface $timestamp   = null,
    ) {}
}
