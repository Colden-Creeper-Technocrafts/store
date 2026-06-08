<?php

namespace App\Shipping\Contracts;

use App\Shipping\DTOs\RateRequest;
use App\Shipping\DTOs\RateResponse;
use App\Shipping\DTOs\ShipmentRequest;
use App\Shipping\DTOs\ShipmentResponse;
use App\Shipping\DTOs\TrackingResponse;

interface ShippingProviderInterface
{
    /** Unique identifier for this provider (e.g. 'shiprocket', 'delhivery', 'manual'). */
    public function getSlug(): string;

    /** Human-readable display name. */
    public function getName(): string;

    /**
     * Return available rate quotes for the given shipment parameters.
     *
     * @return RateResponse[]
     */
    public function getRates(RateRequest $request): array;

    /**
     * Book a shipment with the carrier and return tracking / label details.
     */
    public function createShipment(ShipmentRequest $request): ShipmentResponse;

    /**
     * Cancel an active shipment by AWB number.
     */
    public function cancelShipment(string $awbNumber): bool;

    /**
     * Fetch live tracking status for an AWB number.
     */
    public function getTracking(string $awbNumber): TrackingResponse;

    /**
     * Verify that the stored credentials can authenticate with the provider.
     */
    public function validateCredentials(): bool;

    /**
     * Whether this provider is active and properly configured.
     */
    public function isAvailable(): bool;
}
