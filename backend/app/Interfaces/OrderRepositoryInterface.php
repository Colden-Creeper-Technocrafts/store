<?php

namespace App\Interfaces;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function createFromCart(int $userId, Cart $cart, array $shippingData, ?Coupon $coupon = null): Order;

    public function createFromGuestItems(array $items, array $shippingData, ?Coupon $coupon = null): Order;

    public function listForUser(int $userId): Collection;

    public function findForUser(int $orderId, int $userId): ?Order;

    // ── Admin ──────────────────────────────────────────────────────────────

    public function adminList(array $filters, int $perPage): LengthAwarePaginator;

    public function adminFind(int $orderId): ?Order;

    public function updateStatus(Order $order, string $status, ?int $changedBy = null, ?string $note = null): Order;

    public function updatePaymentStatus(Order $order, string $paymentStatus): Order;

    public function updateTracking(Order $order, ?string $trackingNumber, ?string $trackingUrl): Order;

    public function updateAdminNotes(Order $order, ?string $notes): Order;
}
