<?php

namespace App\Interfaces;

use App\Models\User;

interface AuthRepositoryInterface
{
    public function createCustomer(array $payload): User;

    public function attemptLogin(string $email, string $password): ?User;

    public function logout(User $user): void;
}
