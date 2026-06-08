<?php

namespace App\Interfaces;

use App\Models\Cart;
use App\Models\CartItem;

interface CartRepositoryInterface
{
    public function getOrCreateForUser(int $userId): Cart;

    public function loadWithItems(Cart $cart): Cart;

    public function findItem(Cart $cart, int $productId): ?CartItem;

    public function findItemById(Cart $cart, int $itemId): ?CartItem;

    public function addItem(Cart $cart, int $productId, int $quantity): CartItem;

    public function updateItemQty(CartItem $item, int $quantity): CartItem;

    public function removeItem(CartItem $item): void;

    public function clear(Cart $cart): void;
}
