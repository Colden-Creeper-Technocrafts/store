<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /** Send OTP after guest checkout (called by frontend post-order) */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'    => ['required', 'string', 'max:30'],
            'email'    => ['nullable', 'email'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
        ]);

        $this->otp->sendOrderOtp(
            $data['phone'],
            $data['email'] ?? null,
            isset($data['order_id']) ? (int) $data['order_id'] : null,
        );

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your mobile number.',
        ]);
    }

    /** Verify OTP — registers the user (no auto-login, no token returned) */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'otp'   => ['required', 'string', 'size:6'],
        ]);

        $record = $this->otp->verifyOtp($data['phone'], $data['otp']);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $result = $this->otp->registerFromOtp($record);

        return response()->json([
            'success'     => true,
            'message'     => 'Phone verified successfully.',
            'is_new_user' => $result['is_new_user'],
        ]);
    }

    /** Step 1 of OTP login — send OTP to phone */
    public function loginSend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $this->otp->sendLoginOtp($data['phone']);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent.',
        ]);
    }

    /** Step 2 of OTP login — verify and return token */
    public function loginVerify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'otp'   => ['required', 'string', 'size:6'],
        ]);

        $record = $this->otp->verifyOtp($data['phone'], $data['otp']);

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $auth = $this->otp->registerOrLoginFromOtp($record);

        return response()->json([
            'success' => true,
            'token'   => $auth['token'],
            'role'    => $auth['user']['role'],
            'user'    => $auth['user'],
        ]);
    }

    /** Email verification link — marks email verified and redirects to frontend */
    public function verifyEmail(Request $request)
    {
        $token  = $request->query('token', '');
        $record = $this->otp->verifyEmailToken($token);

        $frontendBase = rtrim(config('app.frontend_url', config('app.url')), '/');

        if (!$record) {
            return redirect("{$frontendBase}/#/email-verified?status=invalid");
        }

        return redirect("{$frontendBase}/#/email-verified?status=success");
    }

    /** Set password for authenticated phone-only users */
    public function setPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['success' => true, 'message' => 'Password set successfully.']);
    }
}
