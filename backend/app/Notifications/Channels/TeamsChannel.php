<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;

/** Uses a Teams "Incoming Webhook" connector — simple text MessageCard payload. */
class TeamsChannel implements NotificationChannelInterface
{
    public function key(): string { return 'teams'; }
    public function displayName(): string { return 'Microsoft Teams'; }
    public function isBroadcast(): bool { return true; }
    public function requiredConfigKeys(): array { return ['webhook_url']; }

    public function send(string $title, string $body, ?array $recipient, array $config): array
    {
        $webhookUrl = $config['webhook_url'] ?? null;

        if (! $webhookUrl) {
            return ['success' => false, 'recipient_label' => 'Teams', 'error' => 'No webhook URL configured.'];
        }

        $response = Http::post($webhookUrl, [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'summary' => $title,
            'title' => $title,
            'text' => $body,
        ]);

        return [
            'success' => $response->successful(),
            'recipient_label' => 'Teams webhook',
            'error' => $response->successful() ? null : $response->body(),
        ];
    }
}
