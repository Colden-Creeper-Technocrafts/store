<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const TWILIO_API = 'https://api.twilio.com/2010-04-01/Accounts/{sid}/Messages.json';

    /**
     * Send an SMS via Twilio.
     */
    public function sendSms(
        string $to,
        string $body,
        string $accountSid,
        string $authToken,
        string $from,
    ): bool {
        $to = $this->toE164($to);
        if (!$to) {
            Log::warning('SmsService: invalid phone number, skipping SMS.');
            return false;
        }

        return $this->twilioSend($accountSid, $authToken, $from, $to, $body);
    }

    /**
     * Send a WhatsApp message via Twilio.
     */
    public function sendWhatsApp(
        string $to,
        string $body,
        string $accountSid,
        string $authToken,
        string $whatsappFrom,
    ): bool {
        $phone = $this->toE164($to);
        if (!$phone) {
            Log::warning('SmsService: invalid phone number, skipping WhatsApp.');
            return false;
        }

        $whatsappTo = "whatsapp:{$phone}";
        if (!str_starts_with($whatsappFrom, 'whatsapp:')) {
            $whatsappFrom = "whatsapp:{$whatsappFrom}";
        }

        return $this->twilioSend($accountSid, $authToken, $whatsappFrom, $whatsappTo, $body);
    }

    private function twilioSend(
        string $accountSid,
        string $authToken,
        string $from,
        string $to,
        string $body,
    ): bool {
        $url = str_replace('{sid}', $accountSid, self::TWILIO_API);

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post($url, compact('From', 'To', 'Body') + ['From' => $from, 'To' => $to, 'Body' => $body]);

        if ($response->failed()) {
            Log::error('SmsService Twilio error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Normalize Indian phone numbers to E.164 (+91XXXXXXXXXX).
     * Falls back to prepending + if already looks international.
     */
    public function toE164(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return "+91{$digits}";
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+91' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return "+{$digits}";
        }

        if (strlen($digits) > 10) {
            return "+{$digits}";
        }

        return null;
    }
}
