<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Shipment;
use App\Models\ShippingProvider;
use App\Shipping\DTOs\ShipmentRequest;
use App\Shipping\ShippingProviderManager;
use Illuminate\Http\JsonResponse;
use Throwable;

class FulfillmentController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ShippingProviderManager  $shipping,
    ) {}

    /**
     * Auto-fulfill an order via the active live shipping provider (Shiprocket / Delhivery).
     * Creates a Shiprocket order, assigns AWB, stores a Shipment record, and marks the order shipped.
     */
    public function fulfill(int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->shipment) {
            return response()->json([
                'message' => 'Order already has a shipment (AWB: ' . $order->shipment->awb_number . ')',
            ], 422);
        }

        // Find first active live provider (skip manual)
        $provider      = null;
        $providerModel = null;

        foreach ($this->shipping->activeProviders() as $p) {
            if ($p->getSlug() !== 'manual') {
                $provider      = $p;
                $providerModel = ShippingProvider::where('slug', $p->getSlug())->first();
                break;
            }
        }

        if (!$provider || !$providerModel) {
            return response()->json([
                'message' => 'No live shipping provider is active. Enable Shiprocket or Delhivery in Admin → Shipping → Providers.',
            ], 422);
        }

        // Total weight from items; floor at 0.1 kg
        $weightKg = max(0.1, (float) $order->items->sum(
            fn ($item) => max(0.1, (float) ($item->weight_kg ?? 0.1)) * $item->quantity,
        ));

        $shipmentRequest = new ShipmentRequest(
            orderId:         $order->id,
            receiverName:    $order->shipping_name,
            receiverPhone:   $order->shipping_phone   ?? '',
            receiverEmail:   $order->shipping_email   ?? '',
            receiverAddress: $order->shipping_address,
            receiverCity:    $order->shipping_city,
            receiverState:   $order->shipping_state   ?? '',
            receiverPincode: $order->shipping_postal_code,
            receiverCountry: $order->shipping_country ?? 'India',
            weightKg:        $weightKg,
            orderAmount:     (float) $order->total,
            items:           $order->items->map(fn ($item) => [
                'name'  => $item->name,
                'sku'   => $item->sku ?? ('SKU-' . $item->id),
                'qty'   => $item->quantity,
                'price' => (float) $item->price,
            ])->all(),
            methodCode: 'standard',
        );

        $result = $provider->createShipment($shipmentRequest);

        if (!$result->success) {
            return response()->json([
                'message' => 'Shipment creation failed: ' . ($result->errorMessage ?? 'Unknown error'),
            ], 422);
        }

        $shipment = Shipment::create([
            'order_id'             => $order->id,
            'shipping_provider_id' => $providerModel->id,
            'shipping_method_id'   => $order->shipping_method_id,
            'awb_number'           => $result->awbNumber,
            'tracking_url'         => $result->trackingUrl,
            'provider_shipment_id' => $result->providerShipmentId,
            'label_url'            => $result->labelUrl,
            'status'               => 'booked',
            'weight_kg'            => $weightKg,
            'shipped_at'           => now(),
            'meta'                 => $result->meta ?? [],
        ]);

        // Update order with AWB + mark shipped
        $this->orders->updateTracking($order, $result->awbNumber, $result->trackingUrl);
        $this->orders->updateStatus(
            $order->fresh(),
            'shipped',
            null,
            'Auto-fulfilled via ' . $provider->getName(),
        );

        return response()->json([
            'success'  => true,
            'shipment' => $this->formatShipment($shipment),
        ]);
    }

    /**
     * Fetch live tracking events from the shipping provider for an order.
     */
    public function tracking(int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $shipment = Shipment::with('provider')
            ->where('order_id', $order->id)
            ->first();

        $awb = $shipment?->awb_number ?? $order->tracking_number;

        if (!$awb) {
            return response()->json(['message' => 'No AWB / tracking number for this order'], 422);
        }

        $providerSlug = $shipment?->provider?->slug;

        if (!$providerSlug) {
            return response()->json(['message' => 'Cannot determine shipping provider for this order'], 422);
        }

        try {
            $provider = $this->shipping->provider($providerSlug);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Provider error: ' . $e->getMessage()], 422);
        }

        $info = $provider->getTracking($awb);

        if (!$info->success) {
            return response()->json(['message' => $info->errorMessage ?? 'Tracking lookup failed'], 422);
        }

        return response()->json([
            'awb_number'         => $info->awbNumber,
            'current_status'     => $info->currentStatus,
            'estimated_delivery' => $info->estimatedDelivery,
            'history'            => array_map(fn ($t) => [
                'status'      => $t->status,
                'location'    => $t->location,
                'description' => $t->description,
                'timestamp'   => $t->timestamp?->format('Y-m-d H:i:s'),
            ], $info->history),
        ]);
    }

    private function formatShipment(Shipment $shipment): array
    {
        return [
            'id'                   => $shipment->id,
            'awb_number'           => $shipment->awb_number,
            'tracking_url'         => $shipment->tracking_url,
            'label_url'            => $shipment->label_url,
            'provider_shipment_id' => $shipment->provider_shipment_id,
            'status'               => $shipment->status,
            'weight_kg'            => (float) $shipment->weight_kg,
            'shipped_at'           => $shipment->shipped_at?->toIso8601String(),
        ];
    }
}
