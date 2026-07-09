<?php

namespace App\Shipping\Providers;

use App\Shipping\AbstractShippingProvider;
use App\Shipping\DTOs\RateRequest;
use App\Shipping\DTOs\RateResponse;
use App\Shipping\DTOs\ShipmentRequest;
use App\Shipping\DTOs\ShipmentResponse;
use App\Shipping\DTOs\TrackingInfo;
use App\Shipping\DTOs\TrackingResponse;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ShiprocketProvider extends AbstractShippingProvider
{
    private const BASE_URL  = 'https://apiv2.shiprocket.in/v1/external';
    private const TOKEN_TTL = 777_600; // 9 days in seconds (tokens valid 10 days)

    // ── ShippingProviderInterface ─────────────────────────────────────────────

    public function validateCredentials(): bool
    {
        try {
            $this->freshToken();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Query Shiprocket's serviceability endpoint and return available courier rates.
     *
     * @return RateResponse[]
     */
    public function getRates(RateRequest $request): array
    {
        try {
            $data = $this->get('/courier/serviceability/', [
                'pickup_postcode'   => $this->setting('pickup_pincode', ''),
                'delivery_postcode' => $request->destinationPincode,
                'cod'               => 0,
                'weight'            => $request->weightKg,
                'length'            => $request->lengthCm ?? $this->setting('default_length', 10),
                'breadth'           => $request->widthCm  ?? $this->setting('default_width', 10),
                'height'            => $request->heightCm ?? $this->setting('default_height', 10),
            ]);

            $couriers = $data['data']['available_courier_companies'] ?? [];

            return collect($couriers)
                ->filter(fn($c) => ($c['blocked'] ?? 1) == 0)
                ->sortBy('rate')
                ->map(fn($c) => new RateResponse(
                    methodCode:   'shiprocket_' . ($c['courier_company_id'] ?? $c['id']),
                    methodName:   $c['courier_name'] ?? 'Shiprocket Courier',
                    providerSlug: 'shiprocket',
                    cost:         (float) ($c['rate'] ?? $c['freight_charge'] ?? 0),
                    minDays:      (int)   ($c['min_delivery_days'] ?? 3),
                    maxDays:      (int)   ($c['max_delivery_days'] ?? 7),
                ))
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('Shiprocket getRates failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Create an order on Shiprocket, assign AWB, and optionally generate a label.
     */
    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        try {
            // Step 1 — create the order
            $orderData = $this->post('/orders/create/adhoc', $this->buildOrderPayload($request));

            if (empty($orderData['order_id'])) {
                return ShipmentResponse::failure(
                    $orderData['message'] ?? 'Shiprocket order creation failed',
                    $orderData
                );
            }

            $srOrderId  = $orderData['order_id'];
            $srShipmentId = $orderData['shipment_id'] ?? null;

            // Step 2 — assign courier and get AWB
            $awbData = $this->post('/courier/assign/awb', ['shipment_id' => $srShipmentId]);

            $awbNumber = $awbData['response']['data']['awb_code']
                ?? $awbData['awb_code']
                ?? null;

            if (!$awbNumber) {
                return ShipmentResponse::failure(
                    $awbData['response']['data']['awb_assign_error']
                        ?? $awbData['message']
                        ?? 'AWB assignment failed',
                    array_merge($orderData, $awbData)
                );
            }

            $courierName = $awbData['response']['data']['courier_name'] ?? null;

            // Step 3 — generate label (best-effort; may not be immediately ready)
            $labelUrl = null;
            try {
                $labelData = $this->post('/courier/generate/label', [
                    'shipment_id' => [$srShipmentId],
                ]);
                $labelUrl = $labelData['label_url'] ?? null;
            } catch (Throwable) {
                // non-fatal: label can be fetched later
            }

            return new ShipmentResponse(
                success:            true,
                awbNumber:          $awbNumber,
                providerShipmentId: (string) $srOrderId,
                labelUrl:           $labelUrl,
                trackingUrl:        "https://shiprocket.co/tracking/{$awbNumber}",
                meta: [
                    'shiprocket_order_id'    => $srOrderId,
                    'shiprocket_shipment_id' => $srShipmentId,
                    'courier_name'           => $courierName,
                ],
            );
        } catch (Throwable $e) {
            Log::error('Shiprocket createShipment failed', [
                'order_id' => $request->orderId,
                'error'    => $e->getMessage(),
            ]);
            return ShipmentResponse::failure($e->getMessage());
        }
    }

    /**
     * Cancel a Shiprocket order by its Shiprocket order ID (stored in shipment meta
     * as 'shiprocket_order_id'). Pass that ID here as $awbNumber.
     */
    public function cancelShipment(string $awbNumber): bool
    {
        try {
            $data = $this->post('/orders/cancel', ['ids' => [$awbNumber]]);
            return ($data['status_code'] ?? 0) == 200
                || str_contains(strtolower($data['message'] ?? ''), 'success');
        } catch (Throwable $e) {
            Log::warning('Shiprocket cancelShipment failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fetch live tracking events for an AWB number.
     */
    public function getTracking(string $awbNumber): TrackingResponse
    {
        try {
            $data = $this->get("/courier/track/awb/{$awbNumber}");

            $trackingData = $data['tracking_data'] ?? null;

            if (!$trackingData) {
                return TrackingResponse::failure('No tracking data returned by Shiprocket');
            }

            $shipmentTrack = $trackingData['shipment_track'][0] ?? [];
            $activities    = $trackingData['shipment_track_activities'] ?? [];

            $history = collect($activities)
                ->map(fn($a) => new TrackingInfo(
                    status:      $a['status'] ?? 'unknown',
                    location:    $a['location'] ?? null,
                    description: $a['activity'] ?? null,
                    timestamp:   !empty($a['date'])
                        ? Carbon::parse($a['date'])->toDateTimeImmutable()
                        : null,
                ))
                ->all();

            return new TrackingResponse(
                success:           true,
                currentStatus:     $shipmentTrack['current_status'] ?? null,
                awbNumber:         $awbNumber,
                estimatedDelivery: $shipmentTrack['edd'] ?? null,
                history:           $history,
            );
        } catch (Throwable $e) {
            Log::warning('Shiprocket getTracking failed', [
                'awb'   => $awbNumber,
                'error' => $e->getMessage(),
            ]);
            return TrackingResponse::failure($e->getMessage());
        }
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function get(string $endpoint, array $query = []): array
    {
        return $this->parse(
            Http::withToken($this->getToken())
                ->get(self::BASE_URL . $endpoint, $query)
        );
    }

    private function post(string $endpoint, array $payload = []): array
    {
        return $this->parse(
            Http::withToken($this->getToken())
                ->post(self::BASE_URL . $endpoint, $payload)
        );
    }

    private function parse(Response $response): array
    {
        $body = $response->json() ?? [];

        if ($response->serverError()) {
            throw new RuntimeException(
                'Shiprocket API error ' . $response->status() . ': ' . ($body['message'] ?? 'unknown error')
            );
        }

        return $body;
    }

    // ── Token management ──────────────────────────────────────────────────────

    private function getToken(): string
    {
        $key = 'shiprocket_token_' . $this->providerModel->id;

        return Cache::remember($key, self::TOKEN_TTL, fn() => $this->freshToken());
    }

    private function freshToken(): string
    {
        $response = Http::post(self::BASE_URL . '/auth/login', [
            'email'    => $this->credential('email'),
            'password' => $this->credential('password'),
        ]);

        $token = $response->json('token');

        if (!$token) {
            throw new RuntimeException(
                'Shiprocket authentication failed: ' . ($response->json('message') ?? 'no token in response')
            );
        }

        return $token;
    }

    // ── Payload builder ───────────────────────────────────────────────────────

    private function buildOrderPayload(ShipmentRequest $request): array
    {
        $orderItems = array_map(fn($item) => [
            'name'          => $item['name'],
            'sku'           => $item['sku'] ?? ('SKU-' . $item['name']),
            'units'         => $item['qty'],
            'selling_price' => $item['price'],
        ], $request->items);

        return [
            'order_id'                => 'ORDER-' . $request->orderId,
            'order_date'              => now()->format('Y-m-d H:i'),
            'pickup_location'         => $this->setting('pickup_location', 'Primary'),
            'billing_customer_name'   => $request->receiverName,
            'billing_last_name'       => '',
            'billing_address'         => $request->receiverAddress,
            'billing_city'            => $request->receiverCity,
            'billing_pincode'         => $request->receiverPincode,
            'billing_state'           => $request->receiverState,
            'billing_country'         => $request->receiverCountry,
            'billing_email'           => $request->receiverEmail,
            'billing_phone'           => $request->receiverPhone,
            'billing_alternate_phone' => '',
            'shipping_is_billing'     => true,
            'order_items'             => $orderItems,
            'payment_method'          => 'Prepaid',
            'sub_total'               => $request->orderAmount,
            'length'                  => $request->lengthCm  ?? $this->setting('default_length', 10),
            'breadth'                 => $request->widthCm   ?? $this->setting('default_width', 10),
            'height'                  => $request->heightCm  ?? $this->setting('default_height', 10),
            'weight'                  => $request->weightKg,
        ];
    }
}
