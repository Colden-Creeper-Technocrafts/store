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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DelhiveryProvider extends AbstractShippingProvider
{
    private const PROD_URL    = 'https://track.delhivery.com';
    private const STAGING_URL = 'https://staging-express.delhivery.com';

    // ── ShippingProviderInterface ─────────────────────────────────────────────

    public function validateCredentials(): bool
    {
        try {
            // Lightweight pincode check as a connectivity test
            $response = $this->get('/c/api/pin-codes/json/', ['filter_codes' => '110001']);
            return isset($response['objects']);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Fetch a rate estimate from Delhivery's charge calculation endpoint.
     * Weight is submitted in grams.
     *
     * @return RateResponse[]
     */
    public function getRates(RateRequest $request): array
    {
        try {
            $weightGrams = (int) round($request->weightKg * 1000);

            $data = $this->get('/api/kinko/v1/invoice/charges/', [
                'md'    => 'S',         // Surface
                'ss'    => 'Delivered',
                'd_pin' => $request->destinationPincode,
                'o_pin' => $this->setting('pickup_pincode', ''),
                'cgm'   => $weightGrams,
                'pt'    => 'Pre-paid',
                'cod'   => 0,
            ]);

            // Response is an array; first element contains the total charge
            $entry = is_array($data) ? ($data[0] ?? $data) : $data;
            $cost  = (float) ($entry['total_amount'] ?? $entry['charge'] ?? 0);

            if ($cost <= 0) {
                return [];
            }

            return [
                new RateResponse(
                    methodCode:   'delhivery_surface',
                    methodName:   'Delhivery Surface',
                    providerSlug: 'delhivery',
                    cost:         $cost,
                    minDays:      (int) $this->setting('min_days', 4),
                    maxDays:      (int) $this->setting('max_days', 7),
                ),
            ];
        } catch (Throwable $e) {
            Log::warning('Delhivery getRates failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Create a waybill on Delhivery.
     * Uses a form-POST where `data` is a JSON-encoded string.
     */
    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        try {
            $payload = $this->buildShipmentPayload($request);
            $data    = $this->formPost('/api/cmu/create.json', $payload);

            $packages = $data['packages'] ?? [];

            if (empty($packages)) {
                $error = $data['cash_pickups_status'] ?? $data['error'] ?? $data['message'] ?? 'No packages in response';
                return ShipmentResponse::failure(
                    is_array($error) ? json_encode($error) : (string) $error,
                    $data
                );
            }

            $package = $packages[0];
            $waybill = $package['waybill'] ?? null;

            if (!$waybill) {
                $packageError = $package['error'] ?? $package['remarks'] ?? 'No waybill in response';
                return ShipmentResponse::failure(
                    is_array($packageError) ? implode(', ', $packageError) : (string) $packageError,
                    $data
                );
            }

            return new ShipmentResponse(
                success:            true,
                awbNumber:          $waybill,
                providerShipmentId: $waybill,
                trackingUrl:        "https://www.delhivery.com/track/package/{$waybill}",
                meta: [
                    'waybill'      => $waybill,
                    'status'       => $package['status'] ?? null,
                    'remarks'      => $package['remarks'] ?? null,
                ],
            );
        } catch (Throwable $e) {
            Log::error('Delhivery createShipment failed', [
                'order_id' => $request->orderId,
                'error'    => $e->getMessage(),
            ]);
            return ShipmentResponse::failure($e->getMessage());
        }
    }

    /**
     * Cancel a shipment by waybill number.
     */
    public function cancelShipment(string $awbNumber): bool
    {
        try {
            $data = $this->formPost('/api/p/edit', [
                'format' => 'json',
                'data'   => json_encode([
                    'shipments'       => [['waybill' => $awbNumber, 'cancellation' => true]],
                    'pickup_location' => ['name' => $this->setting('pickup_name', 'Primary')],
                ]),
            ]);

            $packages = $data['packages'] ?? [];

            // Success when at least one package returned without an error key
            return !empty($packages) && empty($packages[0]['error'] ?? null);
        } catch (Throwable $e) {
            Log::warning('Delhivery cancelShipment failed', [
                'waybill' => $awbNumber,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Fetch live tracking for a waybill.
     */
    public function getTracking(string $awbNumber): TrackingResponse
    {
        try {
            $data         = $this->get('/api/v1/packages/json/', ['waybill' => $awbNumber]);
            $shipmentData = $data['ShipmentData'][0] ?? null;

            if (!$shipmentData) {
                return TrackingResponse::failure("No tracking data for waybill {$awbNumber}");
            }

            $shipment  = $shipmentData['Shipment'] ?? [];
            $scans     = $shipment['Scans'] ?? [];
            $statusMap = $shipment['Status'] ?? [];

            $history = collect($scans)
                ->map(fn($scan) => new TrackingInfo(
                    status:      $scan['ScanDetail']['Scan'] ?? 'unknown',
                    location:    $scan['ScanDetail']['ScannedLocation'] ?? null,
                    description: $scan['ScanDetail']['Instructions'] ?? null,
                    timestamp:   !empty($scan['ScanDetail']['ScanDateTime'])
                        ? Carbon::parse($scan['ScanDetail']['ScanDateTime'])->toDateTimeImmutable()
                        : null,
                ))
                ->all();

            return new TrackingResponse(
                success:           true,
                currentStatus:     $statusMap['Status'] ?? null,
                awbNumber:         $awbNumber,
                estimatedDelivery: $shipment['ExpectedDeliveryDate'] ?? null,
                history:           $history,
            );
        } catch (Throwable $e) {
            Log::warning('Delhivery getTracking failed', [
                'waybill' => $awbNumber,
                'error'   => $e->getMessage(),
            ]);
            return TrackingResponse::failure($e->getMessage());
        }
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    private function get(string $endpoint, array $query = []): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->get($this->baseUrl() . $endpoint, $query);

        return $this->parse($response, $endpoint);
    }

    /**
     * Delhivery's create/edit endpoints expect a form POST with a JSON-encoded `data` field.
     */
    private function formPost(string $endpoint, array $fields): array
    {
        $response = Http::withHeaders($this->authHeaders())
            ->asForm()
            ->post($this->baseUrl() . $endpoint, $fields);

        return $this->parse($response, $endpoint);
    }

    private function parse(\Illuminate\Http\Client\Response $response, string $endpoint): array
    {
        $body = $response->json() ?? [];

        if ($response->serverError()) {
            throw new \RuntimeException(
                "Delhivery API error {$response->status()} on {$endpoint}: "
                . ($body['message'] ?? 'unknown error')
            );
        }

        return $body;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function baseUrl(): string
    {
        return $this->setting('use_staging', false)
            ? self::STAGING_URL
            : self::PROD_URL;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Token ' . $this->credential('api_token')];
    }

    private function buildShipmentPayload(ShipmentRequest $request): array
    {
        $productDesc = implode(', ', array_map(
            fn($i) => $i['name'] . ' x' . $i['qty'],
            $request->items
        ));

        $totalQty    = array_sum(array_column($request->items, 'qty'));
        $weightGrams = (int) round($request->weightKg * 1000);

        $shipment = [
            'name'            => $request->receiverName,
            'add'             => $request->receiverAddress,
            'pin'             => $request->receiverPincode,
            'city'            => $request->receiverCity,
            'state'           => $request->receiverState,
            'country'         => $request->receiverCountry,
            'phone'           => $request->receiverPhone,
            'order'           => 'ORDER-' . $request->orderId,
            'payment'         => 'Prepaid',
            'products_desc'   => $productDesc,
            'cod_amount'      => '0',
            'order_date'      => now()->format('Y-m-d\TH:i:s'),
            'total_amount'    => (string) $request->orderAmount,
            'seller_add'      => $this->setting('pickup_address', ''),
            'seller_name'     => $this->setting('seller_name', ''),
            'seller_inv'      => 'ORDER-' . $request->orderId,
            'quantity'        => (string) $totalQty,
            'waybill'         => '',
            'shipment_length' => (string) ($request->lengthCm  ?? $this->setting('default_length', 10)),
            'shipment_width'  => (string) ($request->widthCm   ?? $this->setting('default_width', 10)),
            'shipment_height' => (string) ($request->heightCm  ?? $this->setting('default_height', 10)),
            'weight'          => (string) $weightGrams,
            'shipping_mode'   => $this->setting('shipping_mode', 'Surface'),
            'address_type'    => 'home',
        ];

        return [
            'format' => 'json',
            'data'   => json_encode([
                'shipments'       => [$shipment],
                'pickup_location' => ['name' => $this->setting('pickup_name', 'Primary')],
            ]),
        ];
    }
}
