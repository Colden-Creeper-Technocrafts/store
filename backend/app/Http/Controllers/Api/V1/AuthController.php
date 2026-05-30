<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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

    private function attemptLogin(Request $request): ?User
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return null;
        }

        return $user;
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

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $user->assignRole('Customer');

        $token = $user->createToken('api-token')->plainTextToken;
        $user = $user->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'token' => $token,
            'role' => $user->getRoleNames()->first(),
            'user' => $this->userPayload($user)
        ]);
    }

    public function login(Request $request)
    {
        $user = $this->attemptLogin($request);

        if (!$user) {
            return $this->invalidCredentialsResponse();
        }

        return $this->authResponse($user);
    }

    public function backstoreLogin(Request $request)
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
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
}
