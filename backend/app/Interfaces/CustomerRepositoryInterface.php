<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function list(array $filters, int $perPage): LengthAwarePaginator;

    public function find(int $id): ?User;
}
