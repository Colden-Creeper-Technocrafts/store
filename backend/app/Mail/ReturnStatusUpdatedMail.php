<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $storeName,
    ) {}

    public function envelope(): Envelope
    {
        $label = ucfirst($this->order->return_status ?? 'updated');
        return new Envelope(subject: "Return for Order #{$this->order->id}: {$label} — {$this->storeName}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.return-status-updated');
    }
}
