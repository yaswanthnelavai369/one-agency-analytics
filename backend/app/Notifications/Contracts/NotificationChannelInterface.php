<?php

namespace App\Notifications\Contracts;

/**
 * Every delivery channel (Email, Slack, Discord, Teams, SMS, WhatsApp)
 * implements this. NotificationManager resolves by key — same registry
 * pattern as IntegrationManager / AIProviderManager. Adding a channel
 * (e.g. Browser Push, once a service-worker subscription flow exists) is
 * one class + one registry line.
 */
interface NotificationChannelInterface
{
    /** Key stored in notification_channels.channel, e.g. "slack". */
    public function key(): string;

    public function displayName(): string;

    /**
     * Broadcast channels (Slack/Discord/Teams) send one message to a shared
     * webhook regardless of recipient count. User-targeted channels
     * (Email/SMS/WhatsApp) send one message per recipient, using that
     * recipient's own contact info (email/phone).
     */
    public function isBroadcast(): bool;

    /** Config keys the agency must supply for this channel to work, e.g. ['webhook_url']. */
    public function requiredConfigKeys(): array;

    /**
     * @param ?array $recipient - null for broadcast channels; ['email' => ..., 'phone' => ...] otherwise
     * @param array $config - the agency's notification_channels.config for this channel
     * @return array{success: bool, recipient_label: string, error: ?string}
     */
    public function send(string $title, string $body, ?array $recipient, array $config): array;
}
