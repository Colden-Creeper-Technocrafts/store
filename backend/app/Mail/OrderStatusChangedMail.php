<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $storeName,
        public readonly string $fromStatus,
    ) {}

    public function envelope(): Envelope
    {
        $label = ucfirst($this->order->status);
        return new Envelope(subject: "Order #{$this->order->id} Update: {$label} — {$this->storeName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-status-changed');
    }
}
