<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Interfaces\AuthRepositoryInterface;
use App\Mail\EmailChangeMail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            'success'       => true,
            'user'          => $this->userPayload($user),
            'pending_email' => $user->pending_email,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $emailChanged = $user->email !== $data['email'];

        $user->update(['name' => $data['name']]);

        if ($emailChanged) {
            $token = Str::random(64);
            $user->update([
                'pending_email'                  => $data['email'],
                'email_change_token'             => $token,
                'email_change_token_expires_at'  => now()->addHours(24),
            ]);
            try {
                Mail::to($data['email'])->send(new EmailChangeMail($user, $token));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('EmailChangeMail send failed: ' . $e->getMessage());
            }
        }

        $user->refresh();

        return response()->json([
            'success'       => true,
            'email_changed' => $emailChanged,
            'pending_email' => $user->pending_email,
            'user'          => $this->userPayload($user),
        ]);
    }

    public function verifyEmailChange(Request $request)
    {
        $token       = (string) $request->query('token', '');
        $frontendUrl = rtrim(env('APP_FRONTEND_URL', config('app.url')), '/');

        if (!$token) {
            return redirect("{$frontendUrl}/profile?email_error=invalid");
        }

        /** @var User|null $user */
        $user = User::where('email_change_token', $token)->first();

        if (!$user || !$user->pending_email) {
            return redirect("{$frontendUrl}/profile?email_error=invalid");
        }

        if (!$user->email_change_token_expires_at || $user->email_change_token_expires_at->isPast()) {
            $user->update([
                'pending_email'                 => null,
                'email_change_token'            => null,
                'email_change_token_expires_at' => null,
            ]);
            return redirect("{$frontendUrl}/profile?email_error=expired");
        }

        $user->update([
            'email'                         => $user->pending_email,
            'pending_email'                 => null,
            'email_change_token'            => null,
            'email_change_token_expires_at' => null,
            'email_verified_at'             => now(),
        ]);

        return redirect("{$frontendUrl}/profile?email_verified=1");
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
