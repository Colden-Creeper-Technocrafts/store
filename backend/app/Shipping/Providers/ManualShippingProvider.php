<?php

namespace App\Shipping\Providers;

use App\Shipping\AbstractShippingProvider;
use App\Shipping\DTOs\RateRequest;
use App\Shipping\DTOs\RateResponse;
use App\Shipping\DTOs\ShipmentRequest;
use App\Shipping\DTOs\ShipmentResponse;
use App\Shipping\DTOs\TrackingResponse;

class ManualShippingProvider extends AbstractShippingProvider
{
    // ── ShippingProviderInterface ─────────────────────────────────────────────

    public function validateCredentials(): bool
    {
        // No external credentials needed for manual shipping
        return true;
    }

    /**
     * Return a single flat-rate option defined in provider settings,
     * or a zero-cost free shipping option if no rate is configured.
     *
     * @return RateResponse[]
     */
    public function getRates(RateRequest $request): array
    {
        $rate    = (float) $this->setting('flat_rate', 0);
        $isFree  = $rate <= 0;

        return [
            new RateResponse(
                methodCode:   'manual_standard',
                methodName:   $this->setting('method_name', 'Standard Shipping'),
                providerSlug: 'manual',
                cost:         $isFree ? 0.0 : $rate,
                minDays:      (int) $this->setting('min_days', 3),
                maxDays:      (int) $this->setting('max_days', 7),
                isFree:       $isFree,
            ),
        ];
    }

    /**
     * Create a placeholder shipment record — the actual tracking details
     * are filled in later by an admin via the dashboard.
     */
    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        // Generate a temporary internal reference; admin will replace with real AWB
        $internalRef = 'MANUAL-' . strtoupper(substr(md5($request->orderId . time()), 0, 8));

        return new ShipmentResponse(
            success:            true,
            awbNumber:          $internalRef,
            providerShipmentId: $internalRef,
            meta: [
                'note' => 'Manual shipment — update AWB and tracking URL via admin dashboard.',
            ],
        );
    }

    /**
     * Manual shipments are cancelled locally; no external call needed.
     */
    public function cancelShipment(string $awbNumber): bool
    {
        return true;
    }

    /**
     * Manual shipments have no automated tracking; return a prompt for the admin.
     */
    public function getTracking(string $awbNumber): TrackingResponse
    {
        return TrackingResponse::failure(
            'Tracking is not available for manual shipments. Please check the carrier website directly.'
        );
    }
}
