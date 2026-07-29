<?php

namespace App\Notifications;

use App\Notifications\Channels\DiscordChannel;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\SlackChannel;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\TeamsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Contracts\NotificationChannelInterface;
use InvalidArgumentException;

class NotificationManager
{
    /** @var array<string, class-string<NotificationChannelInterface>> */
    protected array $channels = [
        'email' => EmailChannel::class,
        'slack' => SlackChannel::class,
        'discord' => DiscordChannel::class,
        'teams' => TeamsChannel::class,
        'sms' => SmsChannel::class,
        'whatsapp' => WhatsAppChannel::class,
        // 'browser_push' => BrowserPushChannel::class, // needs a service-worker subscription flow first
    ];

    public function resolve(string $key): NotificationChannelInterface
    {
        if (! isset($this->channels[$key])) {
            throw new InvalidArgumentException("Unknown notification channel [{$key}].");
        }

        return app($this->channels[$key]);
    }

    /** @return NotificationChannelInterface[] */
    public function all(): array
    {
        return collect($this->channels)->keys()->map(fn ($key) => $this->resolve($key))->all();
    }

    public function catalogue(): array
    {
        return collect($this->all())->map(fn (NotificationChannelInterface $c) => [
            'key' => $c->key(),
            'name' => $c->displayName(),
            'is_broadcast' => $c->isBroadcast(),
            'required_config_keys' => $c->requiredConfigKeys(),
        ])->values()->all();
    }
}
