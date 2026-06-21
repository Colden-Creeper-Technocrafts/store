<?php

namespace App\Repositories;

use App\Interfaces\UserAddressRepositoryInterface;
use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserAddressRepository implements UserAddressRepositoryInterface
{
    public function listForUser(int $userId): Collection
    {
        return UserAddress::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function upsertFromOrder(int $userId, Order $order): void
    {
        $existing = UserAddress::where('user_id', $userId)
            ->where('postal_code', $order->shipping_postal_code)
            ->where('address', $order->shipping_address)
            ->first();

        $isFirst = !UserAddress::where('user_id', $userId)->exists();

        if ($existing) {
            $existing->update([
                'name'    => $order->shipping_name,
                'phone'   => $order->shipping_phone,
                'email'   => $order->shipping_email,
                'city'    => $order->shipping_city,
                'state'   => $order->shipping_state,
                'country' => $order->shipping_country ?? 'India',
            ]);
        } else {
            UserAddress::create([
                'user_id'     => $userId,
                'name'        => $order->shipping_name,
                'phone'       => $order->shipping_phone,
                'email'       => $order->shipping_email,
                'address'     => $order->shipping_address,
                'city'        => $order->shipping_city,
                'state'       => $order->shipping_state,
                'postal_code' => $order->shipping_postal_code,
                'country'     => $order->shipping_country ?? 'India',
                'is_default'  => $isFirst,
            ]);
        }
    }

    public function setDefault(int $userId, int $addressId): UserAddress
    {
        return DB::transaction(function () use ($userId, $addressId) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
            $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);
            $address->update(['is_default' => true]);
            return $address;
        });
    }

    public function delete(int $userId, int $addressId): bool
    {
        $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);
        $wasDefault = $address->is_default;
        $address->delete();

        // Promote the most recently updated address as default if we deleted the default
        if ($wasDefault) {
            $next = UserAddress::where('user_id', $userId)->orderByDesc('updated_at')->first();
            $next?->update(['is_default' => true]);
        }

        return true;
    }
}
