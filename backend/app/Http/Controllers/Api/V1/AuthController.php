<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthRepositoryInterface $auth)
    {
    }

    private function invalidCredentialsResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    private function authResponse(User $user, string $message = 'Login successful'): JsonResponse
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => $message,
            'token' => $token,
            'role' => $user->getRoleNames()->first(),
            'user' => $this->userPayload($user)
        ]);
    }

    private function attemptLogin(LoginRequest $request): ?User
    {
        $payload = $request->validated();

        return $this->auth->attemptLogin($payload['email'], $payload['password']);
    }

    private function userPayload(User $user): array
    {
        $role = $user->getRoleNames()->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
        ];
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->auth->createCustomer($request->validated());

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'token' => $token,
            'role' => $user->getRoleNames()->first(),
            'user' => $this->userPayload($user)
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = $this->attemptLogin($request);

        if (!$user) {
            return $this->invalidCredentialsResponse();
        }

        return $this->authResponse($user);
    }

    public function backstoreLogin(LoginRequest $request)
    {
        $user = $this->attemptLogin($request);

        if (!$user) {
            return $this->invalidCredentialsResponse();
        }

        if (!$user->hasRole('Admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Admin credentials are required for backstore login'
            ], 403);
        }

        return $this->authResponse($user, 'Admin login successful');
    }

    public function profile(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => $this->userPayload($user)
        ]);
    }

    public function logout(Request $request)
    {
        $this->auth->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
}
