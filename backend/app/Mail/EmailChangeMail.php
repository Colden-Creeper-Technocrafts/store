<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verifyUrl;
    public string $userName;

    public function __construct(User $user, string $token)
    {
        $this->userName = $user->name;
        $base           = rtrim(config('app.url'), '/');
        $this->verifyUrl = "{$base}/api/v1/profile/verify-email?token={$token}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your new email address');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-change');
    }
}
