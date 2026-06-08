<?php

namespace App\Interfaces;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function createFromCart(int $userId, Cart $cart, array $shippingData): Order;

    public function createFromGuestItems(array $items, array $shippingData): Order;

    public function listForUser(int $userId): Collection;

    public function findForUser(int $orderId, int $userId): ?Order;
}
