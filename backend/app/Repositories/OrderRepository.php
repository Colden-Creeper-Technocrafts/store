<?php

namespace App\Repositories;

use App\Interfaces\CouponRepositoryInterface;
use App\Interfaces\OrderRepositoryInterface;
use App\Interfaces\UserAddressRepositoryInterface;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Services\NotificationService;
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
        private readonly NotificationService $notifications,
        private readonly UserAddressRepositoryInterface $userAddresses,
    ) {}

    public function createFromCart(int $userId, Cart $cart, array $shippingData, ?Coupon $coupon = null): Order
    {
        $order = DB::transaction(function () use ($userId, $cart, $shippingData, $coupon) {
            $cart->load('items');

            $productIds     = $cart->items->pluck('product_id')->all();
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $defaultVariants = DB::table('product_variants as pv')
                ->whereIn('pv.product_id', $productIds)
                ->whereRaw('pv.id = (SELECT id FROM product_variants WHERE product_id = pv.product_id ORDER BY is_default DESC, id ASC LIMIT 1)')
                ->lockForUpdate()
                ->get(['pv.id', 'pv.product_id', 'pv.price', 'pv.sale_price', 'pv.quantity', 'pv.sku'])
                ->keyBy('product_id');

            foreach ($cart->items as $item) {
                $p = $lockedProducts->get($item->product_id);
                $v = $defaultVariants->get($item->product_id);
                $this->assertStock($p, $item->quantity, $v);
            }

            $subtotal      = 0;
            $totalWeightKg = 0.0;
            foreach ($cart->items as $item) {
                $p              = $lockedProducts->get($item->product_id);
                $v              = $defaultVariants->get($item->product_id);
                $subtotal      += $this->effectivePrice($p, $v) * $item->quantity;
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
                $p     = $lockedProducts->get($item->product_id);
                $v     = $defaultVariants->get($item->product_id);
                $price = $this->effectivePrice($p, $v);

                $order->items()->create([
                    'product_id' => $p->id,
                    'name'       => $p->name,
                    'sku'        => $v?->sku ?? $p->sku,
                    'price'      => $price,
                    'quantity'   => $item->quantity,
                    'subtotal'   => round($price * $item->quantity, 2),
                    'weight_kg'  => ($p->weight > 0) ? (float) $p->weight : null,
                ]);

                $this->decrementStock($p, $v, $item->quantity);
            }

            if ($coupon) {
                $coupon->increment('used_count');
                CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'user_id' => $userId]);
            }

            $cart->items()->delete();

            return $order->load('items');
        });

        $this->notifications->notifyOrderPlaced($order);
        $this->userAddresses->upsertFromOrder($userId, $order);

        return $order;
    }

    public function createFromGuestItems(array $items, array $shippingData, ?Coupon $coupon = null): Order
    {
        $order = DB::transaction(function () use ($items, $shippingData, $coupon) {
            $productIds     = array_column($items, 'product_id');
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $defaultVariants = DB::table('product_variants as pv')
                ->whereIn('pv.product_id', $productIds)
                ->whereRaw('pv.id = (SELECT id FROM product_variants WHERE product_id = pv.product_id ORDER BY is_default DESC, id ASC LIMIT 1)')
                ->lockForUpdate()
                ->get(['pv.id', 'pv.product_id', 'pv.price', 'pv.sale_price', 'pv.quantity', 'pv.sku'])
                ->keyBy('product_id');

            foreach ($items as $item) {
                $p = $lockedProducts->get($item['product_id']);
                $v = $defaultVariants->get($item['product_id']);
                $this->assertStock($p, $item['quantity'], $v);
            }

            $subtotal      = 0;
            $totalWeightKg = 0.0;
            foreach ($items as $item) {
                $p              = $lockedProducts->get($item['product_id']);
                $v              = $defaultVariants->get($item['product_id']);
                $subtotal      += $this->effectivePrice($p, $v) * $item['quantity'];
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
                $p     = $lockedProducts->get($item['product_id']);
                $v     = $defaultVariants->get($item['product_id']);
                $price = $this->effectivePrice($p, $v);

                $order->items()->create([
                    'product_id' => $p->id,
                    'name'       => $p->name,
                    'sku'        => $v?->sku ?? $p->sku,
                    'price'      => $price,
                    'quantity'   => $item['quantity'],
                    'subtotal'   => round($price * $item['quantity'], 2),
                    'weight_kg'  => ($p->weight > 0) ? (float) $p->weight : null,
                ]);

                $this->decrementStock($p, $v, $item['quantity']);
            }

            if ($coupon) {
                $coupon->increment('used_count');
                CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'user_id' => null]);
            }

            return $order->load('items');
        });

        $this->notifications->notifyOrderPlaced($order);

        return $order;
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

    private function assertStock(?Product $product, int $requested, ?object $variant = null): void
    {
        if (!$product) return;
        $stock = (int) ($variant?->quantity ?? $product->quantity);
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

    private function effectivePrice(Product $product, ?object $variant): float
    {
        return (float) ($variant?->sale_price ?? $variant?->price ?? $product->sale_price ?? $product->price);
    }

    private function decrementStock(Product $product, ?object $variant, int $qty): void
    {
        if ($variant) {
            DB::table('product_variants')->where('id', $variant->id)->decrement('quantity', $qty);
        } else {
            $product->decrement('quantity', $qty);
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

        DB::transaction(function () use ($order, $status, $from, $changedBy, $note) {
            $order->update(['status' => $status]);

            OrderStatusHistory::create([
                'order_id'    => $order->id,
                'from_status' => $from,
                'to_status'   => $status,
                'changed_by'  => $changedBy,
                'note'        => $note,
            ]);

            // Restore stock when an order is cancelled (only once — not if already cancelled)
            if ($status === 'cancelled' && $from !== 'cancelled') {
                $order->loadMissing('items');
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        DB::table('product_variants')
                            ->where('id', $item->product_variant_id)
                            ->increment('quantity', $item->quantity);
                    } else {
                        DB::table('products')
                            ->where('id', $item->product_id)
                            ->increment('quantity', $item->quantity);
                    }
                }
            }
        });

        $updated = $order->fresh(['items', 'statusHistory.changedBy']);
        $this->notifications->notifyStatusChanged($updated, $from);

        return $updated;
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
        $updated = $order->fresh(['items']);
        $this->notifications->notifyTrackingUpdated($updated);
        return $updated;
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
        $updated = $order->fresh(['items', 'user']);
        $this->notifications->notifyReturnStatusUpdated($updated);
        return $updated;
    }
}
