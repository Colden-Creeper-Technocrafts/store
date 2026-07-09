<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Shipping\DTOs\RateRequest;
use App\Shipping\ShippingRuleEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(private readonly ShippingRuleEngine $engine) {}

    /**
     * Calculate available shipping rates for a given destination and order.
     *
     * Public endpoint — no auth required (supports guest checkout).
     */
    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pincode'      => ['required', 'string', 'max:20'],
            'state'        => ['nullable', 'string', 'max:100'],
            'weight_kg'    => ['nullable', 'numeric', 'min:0', 'max:500'],
            'order_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $rateRequest = new RateRequest(
            weightKg:           max(0.1, (float) ($data['weight_kg'] ?? 0)),
            orderAmount:        (float) $data['order_amount'],
            destinationPincode: $data['pincode'],
            destinationState:   $data['state'] ?? '',
        );

        // Return no rates for pincodes not in any configured zone
        $zone  = $this->engine->resolveZone($data['pincode'], $data['state'] ?? '');
        $rates = $zone !== null ? $this->engine->availableMethods($rateRequest) : [];

        return response()->json([
            'rates' => array_map(fn($r) => [
                'method_id'         => $r->methodId,
                'method_code'       => $r->methodCode,
                'method_name'       => $r->methodName,
                'provider_slug'     => $r->providerSlug,
                'cost'              => $r->cost,
                'is_free'           => $r->isFree,
                'min_days'          => $r->minDays,
                'max_days'          => $r->maxDays,
                'delivery_estimate' => $r->deliveryEstimate(),
            ], $rates),
        ]);
    }

    /**
     * Check whether delivery is available to a given pincode.
     *
     * Serviceable only when the pincode explicitly resolves to a configured zone.
     * Catch-all rates (zone_id = null) do not count — admin must add the pincode
     * or state to a zone for it to pass this check.
     *
     * Public endpoint — no auth required.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pincode' => ['required', 'string', 'max:20'],
            'state'   => ['nullable', 'string', 'max:100'],
        ]);

        $zone        = $this->engine->resolveZone($data['pincode'], $data['state'] ?? '');
        $serviceable = $zone !== null;

        return response()->json([
            'pincode'     => $data['pincode'],
            'serviceable' => $serviceable,
            'message'     => $serviceable
                ? 'Delivery available'
                : 'Delivery not available to this pincode',
        ]);
    }
}
