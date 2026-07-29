<?php

namespace App\Notifications\Channels;

use App\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;

class SlackChannel implements NotificationChannelInterface
{
    public function key(): string { return 'slack'; }
    public function displayName(): string { return 'Slack'; }
    public function isBroadcast(): bool { return true; }
    public function requiredConfigKeys(): array { return ['webhook_url']; }

    public function send(string $title, string $body, ?array $recipient, array $config): array
    {
        $webhookUrl = $config['webhook_url'] ?? null;

        if (! $webhookUrl) {
            return ['success' => false, 'recipient_label' => 'Slack', 'error' => 'No webhook URL configured.'];
        }

        $response = Http::post($webhookUrl, ['text' => "*{$title}*\n{$body}"]);

        return [
            'success' => $response->successful(),
            'recipient_label' => 'Slack webhook',
            'error' => $response->successful() ? null : $response->body(),
        ];
    }
}
