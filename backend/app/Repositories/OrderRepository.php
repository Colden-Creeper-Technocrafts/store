<?php

namespace App\Repositories;

use App\Interfaces\CouponRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Shipping\DTOs\RateRequest;
use App\Shipping\ShippingRuleEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly CouponRepositoryInterface $coupons,
        private readonly ShippingRuleEngine $engine,
    ) {}

    public function createFromCart(int $userId, Cart $cart, array $shippingData, ?Coupon $coupon = null): Order
    {
        return DB::transaction(function () use ($userId, $cart, $shippingData, $coupon) {
            $cart->load('items');

            $productIds     = $cart->items->pluck('product_id')->all();
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cart->items as $item) {
                $this->assertStock($lockedProducts->get($item->product_id), $item->quantity);
            }

            $subtotal        = 0;
            $totalWeightKg   = 0.0;
            foreach ($cart->items as $item) {
                $p              = $lockedProducts->get($item->product_id);
                $subtotal      += (float) ($p->sale_price ?? $p->price) * $item->quantity;
                $totalWeightKg += (float) ($p->weight ?? 0) * $item->quantity;
            }

            $discount = 0;
            if ($coupon) {
                $this->coupons->validate($coupon, $subtotal);
                $discount = $this->coupons->calculateDiscount($coupon, $subtotal);
            }

            $shipping = $this->resolveShippingCost($shippingData, $subtotal, $totalWeightKg);

            $order = Order::create(array_merge($shippingData, [
                'user_id'            => $userId,
                'status'             => 'pending',
                'subtotal'           => round($subtotal, 2),
                'discount_amount'    => round($discount, 2),
                'shipping_cost'      => $shipping['shipping_cost'],
                'shipping_method_id' => $shipping['shipping_method_id'],
                'total'              => round($subtotal - $discount + $shipping['shipping_cost'], 2),
                'coupon_id'          => $coupon?->id,
                'coupon_code'        => $coupon?->code,
            ]));

            foreach ($cart->items as $item) {
                $product = $lockedProducts->get($item->product_id);
                $price   = (float) ($product->sale_price ?? $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'sku'        => $product->sku,
                    'price'      => $price,
                    'quantity'   => $item->quantity,
                    'subtotal'   => round($price * $item->quantity, 2),
                    'weight_kg'  => ($product->weight > 0) ? (float) $product->weight : null,
                ]);

                $product->decrement('quantity', $item->quantity);
            }

            if ($coupon) {
                $coupon->increment('used_count');
                CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'user_id' => $userId]);
            }

            $cart->items()->delete();

            return $order->load('items');
        });
    }

    public function createFromGuestItems(array $items, array $shippingData, ?Coupon $coupon = null): Order
    {
        return DB::transaction(function () use ($items, $shippingData, $coupon) {
            $productIds     = array_column($items, 'product_id');
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $this->assertStock($lockedProducts->get($item['product_id']), $item['quantity']);
            }

            $subtotal        = 0;
            $totalWeightKg   = 0.0;
            foreach ($items as $item) {
                $p              = $lockedProducts->get($item['product_id']);
                $subtotal      += (float) ($p->sale_price ?? $p->price) * $item['quantity'];
                $totalWeightKg += (float) ($p->weight ?? 0) * $item['quantity'];
            }

            $discount = 0;
            if ($coupon) {
                $this->coupons->validate($coupon, $subtotal);
                $discount = $this->coupons->calculateDiscount($coupon, $subtotal);
            }

            $shipping = $this->resolveShippingCost($shippingData, $subtotal, $totalWeightKg);

            $order = Order::create(array_merge($shippingData, [
                'user_id'            => null,
                'status'             => 'pending',
                'subtotal'           => round($subtotal, 2),
                'discount_amount'    => round($discount, 2),
                'shipping_cost'      => $shipping['shipping_cost'],
                'shipping_method_id' => $shipping['shipping_method_id'],
                'total'              => round($subtotal - $discount + $shipping['shipping_cost'], 2),
                'coupon_id'          => $coupon?->id,
                'coupon_code'        => $coupon?->code,
            ]));

            foreach ($items as $item) {
                $product = $lockedProducts->get($item['product_id']);
                $price   = (float) ($product->sale_price ?? $product->price);

                $order->items()->create([
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'sku'        => $product->sku,
                    'price'      => $price,
                    'quantity'   => $item['quantity'],
                    'subtotal'   => round($price * $item['quantity'], 2),
                    'weight_kg'  => ($product->weight > 0) ? (float) $product->weight : null,
                ]);

                $product->decrement('quantity', $item['quantity']);
            }

            if ($coupon) {
                $coupon->increment('used_count');
                CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'user_id' => null]);
            }

            return $order->load('items');
        });
    }

    private function resolveShippingCost(array $shippingData, float $subtotal, float $totalWeightKg): array
    {
        $methodId = isset($shippingData['shipping_method_id'])
            ? (int) $shippingData['shipping_method_id']
            : null;

        if (!$methodId) {
            return ['shipping_method_id' => null, 'shipping_cost' => 0.0];
        }

        $rateRequest = new RateRequest(
            weightKg:           max(0.1, $totalWeightKg),
            orderAmount:        $subtotal,
            destinationPincode: $shippingData['shipping_postal_code'] ?? '',
            destinationState:   $shippingData['shipping_state'] ?? '',
        );

        $rate = $this->engine->rateForMethod($methodId, $rateRequest);

        return [
            'shipping_method_id' => $methodId,
            'shipping_cost'      => $rate?->cost ?? 0.0,
        ];
    }

    private function assertStock(?Product $product, int $requested): void
    {
        if (!$product) return;
        $stock = (int) $product->quantity;
        if ($stock <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ["\"{$product->name}\" is out of stock."],
            ]);
        }
        if ($requested > $stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$stock} unit(s) of \"{$product->name}\" available."],
            ]);
        }
    }

    public function listForUser(int $userId): Collection
    {
        return Order::where('user_id', $userId)
            ->with('items')
            ->orderByDesc('created_at')
            ->get();
    }

    public function findForUser(int $orderId, int $userId): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->with('items')
            ->first();
    }

    // ── Admin ──────────────────────────────────────────────────────────────

    public function adminList(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Order::with(['items', 'user'])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['search'])) {
            $s        = '%' . $filters['search'] . '%';
            $searchId = ltrim($filters['search'], '#');
            $query->where(function ($q) use ($s, $searchId) {
                $q->where('shipping_name', 'like', $s)
                  ->orWhere('shipping_email', 'like', $s)
                  ->orWhere('id', 'like', $searchId);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['has_return'])) {
            $query->whereNotNull('return_status');
        }

        if (!empty($filters['return_status'])) {
            $query->where('return_status', $filters['return_status']);
        }

        return $query->paginate($perPage);
    }

    public function adminFind(int $orderId): ?Order
    {
        return Order::with(['items', 'user', 'statusHistory.changedBy'])->find($orderId);
    }

    public function updateStatus(Order $order, string $status, ?int $changedBy = null, ?string $note = null): Order
    {
        $from = $order->status;
        $order->update(['status' => $status]);

        OrderStatusHistory::create([
            'order_id'   => $order->id,
            'from_status'=> $from,
            'to_status'  => $status,
            'changed_by' => $changedBy,
            'note'       => $note,
        ]);

        return $order->fresh(['items', 'statusHistory.changedBy']);
    }

    public function updatePaymentStatus(Order $order, string $paymentStatus): Order
    {
        $order->update(['payment_status' => $paymentStatus]);
        return $order->fresh();
    }

    public function updateTracking(Order $order, ?string $trackingNumber, ?string $trackingUrl): Order
    {
        $order->update([
            'tracking_number' => $trackingNumber,
            'tracking_url'    => $trackingUrl,
        ]);
        return $order->fresh();
    }

    public function updateAdminNotes(Order $order, ?string $notes): Order
    {
        $order->update(['admin_notes' => $notes]);
        return $order->fresh();
    }

    public function updateReturnStatus(Order $order, string $returnStatus, ?string $reason = null): Order
    {
        $data = ['return_status' => $returnStatus];

        if ($reason !== null) {
            $data['return_reason'] = $reason;
        }

        if ($returnStatus === 'refunded') {
            $data['payment_status'] = 'refunded';
        }

        $order->update($data);
        return $order->fresh(['items', 'user']);
    }
}
