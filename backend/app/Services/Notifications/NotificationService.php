<?php

namespace App\Services\Notifications;

use App\Models\Agency;
use App\Models\Anomaly;
use App\Models\NotificationChannelConfig;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\NotificationManager;
use Spatie\Permission\PermissionRegistrar;

class NotificationService
{
    public function __construct(protected NotificationManager $manager) {}

    /**
     * Notifies the agency's Owner/Manager team (every enabled channel) plus,
     * separately, that client's own portal user(s) by email (spec: Client role
     * "Receives Notifications" — but broadcast channels like Slack are internal
     * agency tools, not something a client should be pulled into).
     */
    public function notifyForAnomaly(Anomaly $anomaly): void
    {
        $anomaly->loadMissing(['client', 'client.agency']);
        $agency = $anomaly->client->agency;

        $title = "[{$anomaly->severity}] {$anomaly->client->name}: ".ucfirst(str_replace('_', ' ', $anomaly->type));
        $body = $anomaly->message;

        $this->notifyAgencyTeam($agency, $anomaly, $title, $body);
        $this->notifyClientUsers($anomaly, $title, $body);
    }

    protected function notifyAgencyTeam(Agency $agency, Anomaly $anomaly, string $title, string $body): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($agency->id);

        $recipients = User::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->role(['Agency Owner', 'Manager'])
            ->get();

        $enabledChannels = NotificationChannelConfig::where('agency_id', $agency->id)
            ->where('is_enabled', true)
            ->get()
            ->keyBy('channel');

        // Email is always on for the agency team, even with no explicit config row.
        $channelKeys = collect(['email'])->merge($enabledChannels->keys())->unique();

        foreach ($channelKeys as $channelKey) {
            $channel = $this->manager->resolve($channelKey);
            $config = $enabledChannels->get($channelKey)?->config ?? [];

            if ($channel->isBroadcast()) {
                $this->dispatchAndLog($channel, $title, $body, null, $config, $agency->id, $anomaly, null, null);

                continue;
            }

            foreach ($recipients as $user) {
                $this->dispatchAndLog(
                    $channel, $title, $body,
                    ['email' => $user->email, 'phone' => $user->phone],
                    $config, $agency->id, $anomaly, $anomaly->client_id, $user->id
                );
            }
        }
    }

    protected function notifyClientUsers(Anomaly $anomaly, string $title, string $body): void
    {
        $clientUsers = User::where('client_id', $anomaly->client_id)
            ->where('user_type', 'client')
            ->where('status', 'active')
            ->get();

        $emailChannel = $this->manager->resolve('email');

        foreach ($clientUsers as $user) {
            $this->dispatchAndLog(
                $emailChannel, $title, $body,
                ['email' => $user->email, 'phone' => $user->phone],
                [], $anomaly->agency_id, $anomaly, $anomaly->client_id, $user->id
            );
        }
    }

    protected function dispatchAndLog(
        $channel, string $title, string $body, ?array $recipient, array $config,
        int $agencyId, Anomaly $anomaly, ?int $clientId, ?int $userId,
    ): void {
        $result = $channel->send($title, $body, $recipient, $config);

        NotificationLog::create([
            'agency_id' => $agencyId,
            'client_id' => $clientId,
            'user_id' => $userId,
            'anomaly_id' => $anomaly->id,
            'channel' => $channel->key(),
            'recipient' => $result['recipient_label'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'error' => $result['error'],
        ]);
    }
}
