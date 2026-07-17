<?php

namespace App\Repositories;

use App\Interfaces\CustomerRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = User::query()
            ->role('customer')
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?User
    {
        return User::query()
            ->role('customer')
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->with(['orders' => function ($q) {
                $q->latest()
                  ->limit(20)
                  ->select(['id', 'user_id', 'status', 'payment_status', 'total', 'created_at']);
            }])
            ->find($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->assignRole('Customer');
            return $user;
        });
    }
}
