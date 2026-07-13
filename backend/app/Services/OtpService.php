<?php

namespace App\Services;

use App\Mail\EmailVerificationMail;
use App\Models\OtpVerification;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    private const OTP_TTL_MINUTES = 10;

    public function sendOrderOtp(string $phone, string $email, int $orderId): OtpVerification
    {
        return $this->createAndSend($phone, $email, $orderId);
    }

    public function sendLoginOtp(string $phone): OtpVerification
    {
        return $this->createAndSend($phone, null, null);
    }

    private function createAndSend(string $phone, ?string $email, ?int $orderId): OtpVerification
    {
        // Invalidate any previous unexpired OTPs for this phone
        OtpVerification::where('phone', $phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $otp        = $this->generateOtp();
        $emailToken = $email ? Str::random(64) : null;

        $record = OtpVerification::create([
            'phone'       => $phone,
            'email'       => $email,
            'otp'         => $otp,
            'email_token' => $emailToken,
            'order_id'    => $orderId,
            'expires_at'  => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        $this->sendSms($phone, $otp);

        if ($email && $emailToken) {
            $this->sendVerificationEmail($email, $emailToken, $orderId);
        }

        return $record;
    }

    public function verifyOtp(string $phone, string $otp): ?OtpVerification
    {
        $record = OtpVerification::where('phone', $phone)
            ->where('otp', $otp)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return null;
        }

        $record->update(['verified_at' => now()]);

        return $record;
    }

    public function verifyEmailToken(string $token): ?OtpVerification
    {
        $record = OtpVerification::where('email_token', $token)
            ->whereNull('email_verified_at')
            ->first();

        if (!$record) {
            return null;
        }

        $record->update(['email_verified_at' => now()]);

        return $record;
    }

    /**
     * Verify + register only — no token issued. Used for guest checkout OTP.
     * Returns ['is_new_user' => bool].
     */
    public function registerFromOtp(OtpVerification $record): array
    {
        $phone = $record->phone;
        $user  = User::where('phone', $phone)->first();
        $isNew = false;

        if (!$user) {
            $isNew = true;
            $order = $record->order_id ? Order::find($record->order_id) : null;
            $email = $record->email;
            if ($email && User::where('email', $email)->exists()) {
                $email = null;
            }
            $user = User::create([
                'name'              => $order?->shipping_name ?? 'Customer',
                'phone'             => $phone,
                'email'             => $email,
                'password'          => null,
                'phone_verified_at' => now(),
            ]);
            $user->assignRole('Customer');
        } else {
            $user->update(['phone_verified_at' => now()]);
        }

        // Order linking intentionally deferred — order must stay as guest (user_id=null)
        // until Razorpay payment is verified, so the ownership check in createOrder() passes.
        // RazorpayController::verify() handles the link after payment is confirmed.

        return ['is_new_user' => $isNew];
    }

    public function registerOrLoginFromOtp(OtpVerification $record): array
    {
        $phone = $record->phone;
        $user  = User::where('phone', $phone)->first();

        if (!$user && $record->order_id) {
            $order = Order::find($record->order_id);

            $email = $record->email;
            // Avoid duplicate email conflict
            if ($email && User::where('email', $email)->exists()) {
                $email = null;
            }

            $user = User::create([
                'name'              => $order?->shipping_name ?? 'Customer',
                'phone'             => $phone,
                'email'             => $email,
                'password'          => null,
                'phone_verified_at' => now(),
            ]);

            $user->assignRole('Customer');

            // Link the guest order to this user
            if ($order && !$order->user_id) {
                $order->update(['user_id' => $user->id]);
            }
        } elseif (!$user) {
            $user = User::create([
                'name'              => 'Customer',
                'phone'             => $phone,
                'password'          => null,
                'phone_verified_at' => now(),
            ]);

            $user->assignRole('Customer');
        } else {
            $user->update(['phone_verified_at' => now()]);

            // Link unlinked order if present
            if ($record->order_id) {
                $order = Order::find($record->order_id);
                if ($order && !$order->user_id) {
                    $order->update(['user_id' => $user->id]);
                }
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'token' => $token,
            'user'  => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'has_password' => !is_null($user->password),
                'role'         => $user->getRoleNames()->first(),
            ],
        ];
    }

    private function generateOtp(): string
    {
        if (env('USE_STATIC_OTP', '0') === '1') {
            return (string) env('STATIC_OTP', '123456');
        }

        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendSms(string $phone, string $otp): void
    {
        Log::info("OTP for {$phone}: {$otp}");

        $apiKey = config('services.fast2sms.api_key');

        if (!$apiKey) {
            Log::warning('Fast2SMS API key not configured — OTP not sent via SMS.');
            return;
        }

        try {
            $response = Http::withHeaders(['authorization' => $apiKey])
                ->get('https://www.fast2sms.com/dev/bulkV2', [
                    'route'   => 'q',
                    'message' => "Your OTP is {$otp}. Valid for 10 minutes.",
                    'numbers' => $phone,
                    'flash'   => 0,
                ]);

            $body = $response->json();

            if (!($body['return'] ?? false)) {
                Log::warning('Fast2SMS send failed', [
                    'phone'    => $phone,
                    'response' => $body,
                ]);
            } else {
                Log::info('Fast2SMS OTP sent', ['phone' => $phone, 'request_id' => $body['request_id'] ?? null]);
            }
        } catch (\Throwable $e) {
            Log::error('Fast2SMS exception', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    private function sendVerificationEmail(string $email, string $token, ?int $orderId): void
    {
        try {
            Mail::to($email)->send(new EmailVerificationMail($token, $orderId));
        } catch (\Throwable $e) {
            Log::warning("Email verification send failed for {$email}: " . $e->getMessage());
        }
    }
}
