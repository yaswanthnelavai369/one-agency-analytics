<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Mail;

/** Always available — uses the platform's or agency's configured mail settings, no per-agency setup required. */
class EmailChannel implements NotificationChannelInterface
{
    public function key(): string { return 'email'; }
    public function displayName(): string { return 'Email'; }
    public function isBroadcast(): bool { return false; }
    public function requiredConfigKeys(): array { return []; }

    public function send(string $title, string $body, ?array $recipient, array $config): array
    {
        $email = $recipient['email'] ?? null;

        if (! $email) {
            return ['success' => false, 'recipient_label' => 'unknown', 'error' => 'Recipient has no email address.'];
        }

        try {
            Mail::raw($body, function ($message) use ($email, $title) {
                $message->to($email)->subject($title);
            });

            return ['success' => true, 'recipient_label' => $email, 'error' => null];
        } catch (\Throwable $e) {
            return ['success' => false, 'recipient_label' => $email, 'error' => $e->getMessage()];
        }
    }
}
