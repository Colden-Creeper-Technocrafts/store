<?php

namespace App\Shipping;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Shipping\DTOs\RateRequest;
use App\Shipping\DTOs\RateResponse;

class ShippingRuleEngine
{
    /**
     * Return all available shipping options with locally-calculated costs.
     *
     * This uses the shipping_rates table (no external API calls).
     * Use ShippingProviderManager->provider($slug)->getRates() for live carrier quotes.
     *
     * @return RateResponse[]
     */
    public function availableMethods(RateRequest $request): array
    {
        $zone   = $this->resolveZone($request->destinationPincode, $request->destinationState);
        $zoneId = $zone?->id;

        $methods = ShippingMethod::with(['provider', 'rates'])
            ->where('is_active', true)
            ->whereHas('provider', fn($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        $results = [];

        foreach ($methods as $method) {
            $rate = $this->findRate($method, $zoneId, $request->weightKg, $request->orderAmount);

            if ($rate === null) {
                continue;
            }

            $cost = $this->calculateCost($rate, $request->weightKg);

            $results[] = new RateResponse(
                methodCode:   $method->code,
                methodName:   $method->name,
                providerSlug: $method->provider->slug,
                cost:         $cost,
                minDays:      $method->min_days,
                maxDays:      $method->max_days,
                isFree:       $rate->is_free || $cost == 0.0,
                methodId:     $method->id,
            );
        }

        return $results;
    }

    /**
     * Calculate the cost for one specific method given the request parameters.
     * Returns null if no applicable rate rule exists.
     */
    public function rateForMethod(int $methodId, RateRequest $request): ?RateResponse
    {
        $method = ShippingMethod::with('provider')->find($methodId);

        if (!$method || !$method->is_active || !$method->provider->is_active) {
            return null;
        }

        $zone   = $this->resolveZone($request->destinationPincode, $request->destinationState);
        $rate   = $this->findRate($method, $zone?->id, $request->weightKg, $request->orderAmount);

        if ($rate === null) {
            return null;
        }

        $cost = $this->calculateCost($rate, $request->weightKg);

        return new RateResponse(
            methodCode:   $method->code,
            methodName:   $method->name,
            providerSlug: $method->provider->slug,
            cost:         $cost,
            minDays:      $method->min_days,
            maxDays:      $method->max_days,
            isFree:       $rate->is_free || $cost == 0.0,
            methodId:     $method->id,
        );
    }

    // ── Zone resolution ───────────────────────────────────────────────────────

    /**
     * Resolve a destination to a ShippingZone using three-pass specificity:
     *   1. Exact 6-digit pincode match
     *   2. 3-digit pincode prefix match
     *   3. State name match
     *
     * Returns null when no zone matches (only catch-all rates apply).
     */
    public function resolveZone(string $pincode, string $state): ?ShippingZone
    {
        $pincode = trim($pincode);
        $state   = trim($state);
        $prefix  = substr($pincode, 0, 3);

        // Build one query covering all three types, ordered by specificity
        $location = ShippingZoneLocation::with('zone')
            ->whereHas('zone', fn($q) => $q->where('is_active', true))
            ->where(function ($q) use ($pincode, $prefix, $state) {
                $q->where(fn($q2) => $q2->where('type', 'pincode')->where('value', $pincode))
                  ->orWhere(fn($q2) => $q2->where('type', 'pincode_prefix')->where('value', $prefix))
                  ->orWhere(fn($q2) => $q2->where('type', 'state')->where('value', $state));
            })
            // Exact pincode (priority 1) → prefix (2) → state (3)
            ->orderByRaw("FIELD(type, 'pincode', 'pincode_prefix', 'state')")
            ->first();

        return $location?->zone;
    }

    // ── Rate matching ─────────────────────────────────────────────────────────

    /**
     * Find the most applicable rate rule for a method, zone, weight, and order amount.
     *
     * Priority:
     *   - Zone-specific rates beat catch-all (shipping_zone_id IS NULL) rates.
     *   - Within the same specificity level, lower sort_order wins.
     */
    public function findRate(
        ShippingMethod $method,
        ?int           $zoneId,
        float          $weightKg,
        float          $orderAmount
    ): ?ShippingRate {
        return ShippingRate::where('shipping_method_id', $method->id)
            // Zone: match exact zone OR catch-all (null zone)
            ->where(function ($q) use ($zoneId) {
                $q->whereNull('shipping_zone_id');
                if ($zoneId !== null) {
                    $q->orWhere('shipping_zone_id', $zoneId);
                }
            })
            // Weight bounds
            ->where('min_weight_kg', '<=', $weightKg)
            ->where(function ($q) use ($weightKg) {
                $q->whereNull('max_weight_kg')
                  ->orWhere('max_weight_kg', '>=', $weightKg);
            })
            // Order amount bounds (optional)
            ->where(function ($q) use ($orderAmount) {
                $q->whereNull('min_order_amount')
                  ->orWhere('min_order_amount', '<=', $orderAmount);
            })
            ->where(function ($q) use ($orderAmount) {
                $q->whereNull('max_order_amount')
                  ->orWhere('max_order_amount', '>=', $orderAmount);
            })
            // Zone-specific before catch-all, then by sort_order
            ->orderByRaw('(shipping_zone_id IS NULL) ASC')
            ->orderBy('sort_order')
            ->first();
    }

    // ── Cost calculation ──────────────────────────────────────────────────────

    /**
     * Apply the rate formula: base_rate + max(0, weight − min_weight_kg) × per_kg_rate
     */
    public function calculateCost(ShippingRate $rate, float $weightKg): float
    {
        if ($rate->is_free) {
            return 0.0;
        }

        $minWeight   = (float) $rate->min_weight_kg;
        $extraWeight = max(0.0, $weightKg - $minWeight);
        $cost        = (float) $rate->base_rate + $extraWeight * (float) $rate->per_kg_rate;

        return round($cost, 2);
    }
}
