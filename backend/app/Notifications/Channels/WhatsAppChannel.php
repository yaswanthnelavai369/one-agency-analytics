<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;

/** Twilio's WhatsApp Business API — same account as SMS, different From/To prefix. */
class WhatsAppChannel implements NotificationChannelInterface
{
    public function key(): string { return 'whatsapp'; }
    public function displayName(): string { return 'WhatsApp'; }
    public function isBroadcast(): bool { return false; }
    public function requiredConfigKeys(): array { return []; }

    public function send(string $title, string $body, ?array $recipient, array $config): array
    {
        $phone = $recipient['phone'] ?? null;

        if (! $phone) {
            return ['success' => false, 'recipient_label' => 'unknown', 'error' => 'Recipient has no phone number on file.'];
        }

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from_whatsapp');

        if (! $sid || ! $token || ! $from) {
            return ['success' => false, 'recipient_label' => $phone, 'error' => 'Twilio WhatsApp is not configured (TWILIO_SID/TWILIO_TOKEN/TWILIO_FROM_WHATSAPP).'];
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => "whatsapp:{$phone}",
                'From' => "whatsapp:{$from}",
                'Body' => "{$title}\n\n{$body}",
            ]);

        return [
            'success' => $response->successful(),
            'recipient_label' => $phone,
            'error' => $response->successful() ? null : ($response->json('message') ?? $response->body()),
        ];
    }
}
