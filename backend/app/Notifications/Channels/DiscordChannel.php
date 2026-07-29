<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;

class DiscordChannel implements NotificationChannelInterface
{
    public function key(): string { return 'discord'; }
    public function displayName(): string { return 'Discord'; }
    public function isBroadcast(): bool { return true; }
    public function requiredConfigKeys(): array { return ['webhook_url']; }

    public function send(string $title, string $body, ?array $recipient, array $config): array
    {
        $webhookUrl = $config['webhook_url'] ?? null;

        if (! $webhookUrl) {
            return ['success' => false, 'recipient_label' => 'Discord', 'error' => 'No webhook URL configured.'];
        }

        $response = Http::post($webhookUrl, ['content' => "**{$title}**\n{$body}"]);

        return [
            'success' => $response->successful(),
            'recipient_label' => 'Discord webhook',
            'error' => $response->successful() ? null : $response->body(),
        ];
    }
}
