<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayController extends Controller
{
    private function api(): Api
    {
        return new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret'),
        );
    }

    /**
     * Create a Razorpay order for an existing pending order.
     * Works for both authenticated users and guest orders.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::findOrFail($data['order_id']);

        // Auth orders must belong to the current user
        if ($order->user_id !== null && $order->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent re-initiating payment for an already-paid order
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid.'], 422);
        }

        $amountPaise = (int) round($order->total * 100); // Razorpay requires paise

        $razorpayOrder = $this->api()->order->create([
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'receipt'         => 'order_' . $order->id,
            'payment_capture' => 1,
        ]);

        $order->update(['razorpay_order_id' => $razorpayOrder->id]);

        return response()->json([
            'key'               => config('services.razorpay.key_id'),
            'razorpay_order_id' => $razorpayOrder->id,
            'amount'            => $razorpayOrder->amount,
            'currency'          => $razorpayOrder->currency,
            'order_id'          => $order->id,
            'name'              => config('app.name'),
            'description'       => 'Order #' . $order->id,
            'prefill'           => [
                'name'    => $order->shipping_name,
                'email'   => $order->shipping_email,
                'contact' => $order->shipping_phone ?? '',
            ],
        ]);
    }

    /**
     * Verify payment signature after Razorpay modal success callback.
     * Marks the order as paid on valid signature.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'            => ['required', 'integer', 'exists:orders,id'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id'   => ['required', 'string'],
            'razorpay_signature'  => ['required', 'string'],
        ]);

        $order = Order::findOrFail($data['order_id']);

        if ($order->user_id !== null && $order->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $this->api()->utility->verifyPaymentSignature([
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);
        } catch (SignatureVerificationError) {
            return response()->json(['message' => 'Payment verification failed. Please contact support.'], 422);
        }

        $order->update([
            'status'          => 'confirmed',
            'payment_status'  => 'paid',
            'payment_gateway' => 'razorpay',
            'payment_id'      => $data['razorpay_payment_id'],
        ]);

        // Link guest-OTP order to its registered user now that payment is confirmed
        if (!$order->user_id) {
            $otpRecord = OtpVerification::where('order_id', $order->id)
                ->whereNotNull('verified_at')
                ->latest('verified_at')
                ->first();
            if ($otpRecord) {
                $user = User::where('phone', $otpRecord->phone)->first();
                if ($user) {
                    $order->update(['user_id' => $user->id]);
                }
            }
        }

        return response()->json(['success' => true, 'order_id' => $order->id]);
    }

    /**
     * Razorpay webhook — handles async payment events.
     * Must be registered in the Razorpay dashboard and excluded from CSRF / auth middleware.
     */
    public function webhook(Request $request): JsonResponse
    {
        $webhookSecret = config('services.razorpay.webhook_secret');

        if ($webhookSecret) {
            $signature = $request->header('X-Razorpay-Signature', '');
            $body      = $request->getContent();
            $expected  = hash_hmac('sha256', $body, $webhookSecret);

            if (!hash_equals($expected, $signature)) {
                return response()->json(['message' => 'Invalid signature'], 400);
            }
        }

        $event   = $request->input('event');
        $payload = $request->input('payload', []);

        if ($event === 'payment.captured') {
            $entity          = $payload['payment']['entity'] ?? [];
            $razorpayOrderId = $entity['order_id'] ?? null;
            $paymentId       = $entity['id'] ?? null;

            if ($razorpayOrderId) {
                $order = Order::where('razorpay_order_id', $razorpayOrderId)->first();
                if ($order && $order->payment_status !== 'paid') {
                    $order->update([
                        'status'          => 'confirmed',
                        'payment_status'  => 'paid',
                        'payment_gateway' => 'razorpay',
                        'payment_id'      => $paymentId,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
