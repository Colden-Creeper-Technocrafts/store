<?php

namespace App\Services;

use App\Mail\OrderPlacedMail;
use App\Mail\OrderStatusChangedMail;
use App\Mail\ReturnStatusUpdatedMail;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(private readonly SmsService $sms) {}

    // ── Public triggers ──────────────────────────────────────────────────────

    public function notifyOrderPlaced(Order $order): void
    {
        $this->dispatch('order_placed', $order);
    }

    public function notifyStatusChanged(Order $order, string $fromStatus): void
    {
        $this->dispatch('status_changed', $order, fromStatus: $fromStatus);
    }

    public function notifyTrackingUpdated(Order $order): void
    {
        // Treat shipping a tracking update as a "shipped" status notification
        if ($order->tracking_number) {
            $this->dispatch('status_changed', $order, fromStatus: 'processing');
        }
    }

    public function notifyReturnStatusUpdated(Order $order): void
    {
        $this->dispatch('return_updated', $order);
    }

    // ── Core dispatcher ──────────────────────────────────────────────────────

    private function dispatch(string $event, Order $order, string $fromStatus = ''): void
    {
        $setting = StoreSetting::active();
        $config  = $setting?->notification_config ?? [];
        $name    = $setting?->store_name ?? config('app.name');

        $order->loadMissing('items');

        // Email
        if ($this->channelEnabled($config, 'email', $event)) {
            $recipient = $order->shipping_email;
            if ($recipient) {
                $this->trySend('email', $event, $order->id, $recipient, function () use ($event, $order, $name, $fromStatus, $recipient) {
                    $mailable = match ($event) {
                        'order_placed'   => new OrderPlacedMail($order, $name),
                        'status_changed' => new OrderStatusChangedMail($order, $name, $fromStatus),
                        'return_updated' => new ReturnStatusUpdatedMail($order, $name),
                        default          => null,
                    };
                    if ($mailable) {
                        Mail::to($recipient)->send($mailable);
                    }
                });
            }
        }

        // SMS
        if ($this->channelEnabled($config, 'sms', $event)) {
            $phone = $order->shipping_phone;
            if ($phone) {
                $smsConf = $config['sms'] ?? [];
                $this->trySend('sms', $event, $order->id, $phone, function () use ($event, $order, $phone, $smsConf) {
                    $message = $this->buildSmsMessage($event, $order);
                    $this->sms->sendSms(
                        $phone,
                        $message,
                        $smsConf['account_sid'] ?? '',
                        $smsConf['auth_token'] ?? '',
                        $smsConf['from_number'] ?? '',
                    );
                });
            }
        }

        // WhatsApp
        if ($this->channelEnabled($config, 'whatsapp', $event)) {
            $phone = $order->shipping_phone;
            if ($phone) {
                $waConf = $config['whatsapp'] ?? [];
                $this->trySend('whatsapp', $event, $order->id, $phone, function () use ($event, $order, $phone, $waConf, $config) {
                    $message = $this->buildSmsMessage($event, $order);
                    $smsConf = $config['sms'] ?? [];
                    $this->sms->sendWhatsApp(
                        $phone,
                        $message,
                        $smsConf['account_sid'] ?? ($waConf['account_sid'] ?? ''),
                        $smsConf['auth_token']  ?? ($waConf['auth_token'] ?? ''),
                        $waConf['from_number']  ?? '',
                    );
                });
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function channelEnabled(array $config, string $channel, string $event): bool
    {
        return ($config[$channel]['enabled'] ?? false)
            && ($config[$channel]['events'][$event] ?? false);
    }

    private function trySend(string $channel, string $event, ?int $orderId, string $recipient, callable $fn): void
    {
        try {
            $fn();
            NotificationLog::create([
                'order_id'  => $orderId,
                'channel'   => $channel,
                'event'     => $event,
                'recipient' => $recipient,
                'status'    => 'sent',
            ]);
        } catch (\Throwable $e) {
            Log::error("NotificationService [{$channel}/{$event}] failed", ['error' => $e->getMessage()]);
            NotificationLog::create([
                'order_id'     => $orderId,
                'channel'      => $channel,
                'event'        => $event,
                'recipient'    => $recipient,
                'status'       => 'failed',
                'error_message'=> $e->getMessage(),
            ]);
        }
    }

    private function buildSmsMessage(string $event, Order $order): string
    {
        $name  = $order->shipping_name ?? 'Customer';
        $id    = $order->id;
        $total = '₹' . number_format((float) $order->total, 2);

        return match ($event) {
            'order_placed' => "Hi {$name}! Your order #{$id} has been confirmed. Total: {$total}. Thank you for shopping with us!",

            'status_changed' => match ($order->status) {
                'processing' => "Hi {$name}! Your order #{$id} is being processed. We'll notify you when it ships.",
                'shipped'    => $order->tracking_number
                    ? "Hi {$name}! Order #{$id} has been shipped. Tracking: {$order->tracking_number}" . ($order->tracking_url ? " | {$order->tracking_url}" : '') . '.'
                    : "Hi {$name}! Your order #{$id} has been shipped and is on its way!",
                'delivered'  => "Hi {$name}! Your order #{$id} has been delivered. We hope you enjoy it!",
                'cancelled'  => "Hi {$name}! Your order #{$id} has been cancelled. Contact us if you have questions.",
                default      => "Hi {$name}! Your order #{$id} status has been updated to: {$order->status}.",
            },

            'return_updated' => match ($order->return_status) {
                'approved'  => "Hi {$name}! Your return for order #{$id} has been approved. Ship the item(s) back as instructed.",
                'rejected'  => "Hi {$name}! Your return for order #{$id} has been rejected. Contact us for details.",
                'refunded'  => "Hi {$name}! Your refund of {$total} for order #{$id} has been processed. Allow 5-7 business days.",
                default     => "Hi {$name}! Your return request for order #{$id} has been updated.",
            },

            default => "Hi {$name}! Update regarding your order #{$id}.",
        };
    }
}
