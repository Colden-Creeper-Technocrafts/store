<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CreateUserRequest;
use App\Mail\WelcomeCustomerMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminUsersController
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 20), 100);

        $paginator = User::with('roles')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'users' => collect($paginator->items())->map(fn(User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->getRoleNames()->first() ?? 'Unknown',
                'created_at' => $u->created_at,
            ]),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $plain = Str::password(12);

        $user = User::create([
            'name'              => $request->input('name'),
            'email'             => $request->input('email'),
            'phone'             => $request->input('phone'),
            'password'          => $plain,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $user->assignRole($request->input('role'));

        Mail::to($user->email)->send(new WelcomeCustomerMail(
            userName:      $user->name,
            userEmail:     $user->email,
            plainPassword: $plain,
        ));

        return response()->json([
            'success' => true,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $request->input('role'),
                'created_at' => $user->created_at,
            ],
        ], 201);
    }
}
