<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Shipping\ShippingProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdminShippingController extends Controller
{
    public function __construct(private readonly ShippingProviderManager $providers) {}

    // ── Providers ─────────────────────────────────────────────────────────────

    public function providers(): JsonResponse
    {
        $providers = ShippingProvider::with('methods')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($p) => $this->formatProvider($p));

        return response()->json(['providers' => $providers]);
    }

    public function updateProvider(Request $request, int $id): JsonResponse
    {
        $provider = ShippingProvider::findOrFail($id);

        $data = $request->validate([
            'is_active'   => ['sometimes', 'boolean'],
            'settings'    => ['sometimes', 'array'],
            'credentials' => ['sometimes', 'array'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ]);

        if (isset($data['credentials'])) {
            // Merge with existing so partial updates don't wipe other keys
            $existing = $provider->credentials ?? [];
            $provider->credentials = array_merge($existing, $data['credentials']);
            unset($data['credentials']);
        }

        if (isset($data['settings'])) {
            $existing = $provider->settings ?? [];
            $provider->settings = array_merge($existing, $data['settings']);
            unset($data['settings']);
        }

        $provider->fill($data)->save();

        return response()->json(['provider' => $this->formatProvider($provider->fresh())]);
    }

    public function validateProvider(int $id): JsonResponse
    {
        $provider = ShippingProvider::findOrFail($id);

        try {
            $driver = $this->providers->provider($provider->slug);
            $driver->refresh();
            $ok = $driver->validateCredentials();
        } catch (Throwable $e) {
            return response()->json(['valid' => false, 'message' => $e->getMessage()]);
        }

        return response()->json([
            'valid'   => $ok,
            'message' => $ok ? 'Credentials are valid.' : 'Credentials could not be verified.',
        ]);
    }

    // ── Zones ─────────────────────────────────────────────────────────────────

    public function zones(): JsonResponse
    {
        $zones = ShippingZone::with('locations')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['zones' => $zones]);
    }

    public function storeZone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:shipping_zones'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);

        $zone = ShippingZone::create($data);

        return response()->json(['zone' => $zone->load('locations')], 201);
    }

    public function updateZone(Request $request, int $id): JsonResponse
    {
        $zone = ShippingZone::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100', 'unique:shipping_zones,name,' . $id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);

        $zone->update($data);

        return response()->json(['zone' => $zone->fresh('locations')]);
    }

    public function destroyZone(int $id): JsonResponse
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->delete();

        return response()->json(['message' => 'Zone deleted.']);
    }

    public function storeZoneLocation(Request $request, int $zoneId): JsonResponse
    {
        ShippingZone::findOrFail($zoneId);

        $data = $request->validate([
            'type'  => ['required', 'in:' . implode(',', ShippingZoneLocation::TYPES)],
            'value' => ['required', 'string', 'max:20'],
        ]);

        $location = ShippingZoneLocation::firstOrCreate([
            'shipping_zone_id' => $zoneId,
            'type'             => $data['type'],
            'value'            => $data['value'],
        ]);

        return response()->json(['location' => $location], 201);
    }

    public function destroyZoneLocation(int $zoneId, int $locationId): JsonResponse
    {
        $location = ShippingZoneLocation::where('shipping_zone_id', $zoneId)
            ->findOrFail($locationId);

        $location->delete();

        return response()->json(['message' => 'Location removed.']);
    }

    // ── Methods ───────────────────────────────────────────────────────────────

    public function methods(): JsonResponse
    {
        $methods = ShippingMethod::with('provider')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['methods' => $methods]);
    }

    public function storeMethod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipping_provider_id' => ['required', 'exists:shipping_providers,id'],
            'name'                 => ['required', 'string', 'max:100'],
            'code'                 => ['required', 'string', 'max:50'],
            'description'          => ['nullable', 'string', 'max:500'],
            'min_days'             => ['integer', 'min:0'],
            'max_days'             => ['integer', 'min:0'],
            'is_active'            => ['boolean'],
            'sort_order'           => ['integer', 'min:0'],
        ]);

        $method = ShippingMethod::create($data);

        return response()->json(['method' => $method->load('provider')], 201);
    }

    public function updateMethod(Request $request, int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'min_days'    => ['integer', 'min:0'],
            'max_days'    => ['integer', 'min:0'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);

        $method->update($data);

        return response()->json(['method' => $method->fresh('provider')]);
    }

    public function destroyMethod(int $id): JsonResponse
    {
        $method = ShippingMethod::findOrFail($id);
        $method->delete();

        return response()->json(['message' => 'Method deleted.']);
    }

    // ── Rates ─────────────────────────────────────────────────────────────────

    public function rates(int $methodId): JsonResponse
    {
        ShippingMethod::findOrFail($methodId);

        $rates = ShippingRate::with('zone')
            ->where('shipping_method_id', $methodId)
            ->orderByRaw('(shipping_zone_id IS NULL) DESC')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['rates' => $rates]);
    }

    public function storeRate(Request $request, int $methodId): JsonResponse
    {
        ShippingMethod::findOrFail($methodId);

        $data = $request->validate([
            'shipping_zone_id' => ['nullable', 'exists:shipping_zones,id'],
            'min_weight_kg'    => ['numeric', 'min:0'],
            'max_weight_kg'    => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'base_rate'        => ['numeric', 'min:0'],
            'per_kg_rate'      => ['numeric', 'min:0'],
            'is_free'          => ['boolean'],
            'sort_order'       => ['integer', 'min:0'],
        ]);

        $rate = ShippingRate::create(array_merge($data, ['shipping_method_id' => $methodId]));

        return response()->json(['rate' => $rate->load('zone')], 201);
    }

    public function updateRate(Request $request, int $id): JsonResponse
    {
        $rate = ShippingRate::findOrFail($id);

        $data = $request->validate([
            'shipping_zone_id' => ['nullable', 'exists:shipping_zones,id'],
            'min_weight_kg'    => ['numeric', 'min:0'],
            'max_weight_kg'    => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'base_rate'        => ['numeric', 'min:0'],
            'per_kg_rate'      => ['numeric', 'min:0'],
            'is_free'          => ['boolean'],
            'sort_order'       => ['integer', 'min:0'],
        ]);

        $rate->update($data);

        return response()->json(['rate' => $rate->fresh('zone')]);
    }

    public function destroyRate(int $id): JsonResponse
    {
        ShippingRate::findOrFail($id)->delete();

        return response()->json(['message' => 'Rate deleted.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatProvider(ShippingProvider $provider): array
    {
        return [
            'id'          => $provider->id,
            'name'        => $provider->name,
            'slug'        => $provider->slug,
            'description' => $provider->description,
            'is_active'   => $provider->is_active,
            'settings'    => $provider->settings,
            'sort_order'  => $provider->sort_order,
            // credentials intentionally omitted from response
            'has_credentials' => !empty($provider->credentials),
            'methods_count'   => $provider->methods->count(),
        ];
    }
}
