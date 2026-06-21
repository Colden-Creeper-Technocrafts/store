<?php

namespace App\Interfaces;

use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Support\Collection;

interface UserAddressRepositoryInterface
{
    public function listForUser(int $userId): Collection;
    public function upsertFromOrder(int $userId, Order $order): void;
    public function setDefault(int $userId, int $addressId): UserAddress;
    public function delete(int $userId, int $addressId): bool;
}
