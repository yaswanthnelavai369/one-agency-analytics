<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannelConfig;
use App\Models\NotificationLog;
use App\Notifications\NotificationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationManager $manager) {}

    public function catalogue(): JsonResponse
    {
        return response()->json(['data' => $this->manager->catalogue()]);
    }

    /** Current per-channel enabled/config state for the agency. */
    public function index(Request $request): JsonResponse
    {
        $configs = NotificationChannelConfig::where('agency_id', $request->user()->agency_id)
            ->get(['channel', 'is_enabled', 'config'])
            ->keyBy('channel');

        return response()->json(['data' => $configs]);
    }

    public function update(Request $request, string $channel): JsonResponse
    {
        abort_unless(in_array($channel, array_column($this->manager->catalogue(), 'key')), 404);

        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'config' => ['sometimes', 'array'],
        ]);

        $config = NotificationChannelConfig::updateOrCreate(
            ['agency_id' => $request->user()->agency_id, 'channel' => $channel],
            ['is_enabled' => $data['is_enabled'], 'config' => $data['config'] ?? []]
        );

        return response()->json(['data' => $config]);
    }

    /** Sends a real test message through the channel using its current saved config. */
    public function test(Request $request, string $channel): JsonResponse
    {
        $channelInstance = $this->manager->resolve($channel);

        $config = NotificationChannelConfig::where('agency_id', $request->user()->agency_id)
            ->where('channel', $channel)
            ->first();

        $recipient = $channelInstance->isBroadcast() ? null : [
            'email' => $request->user()->email,
            'phone' => $request->user()->phone,
        ];

        $result = $channelInstance->send(
            'Test notification',
            'This is a test notification from Search29 Analytics AI.',
            $recipient,
            $config?->config ?? []
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = NotificationLog::where('agency_id', $request->user()->agency_id)
            ->latest()
            ->limit(100)
            ->get();

        return response()->json(['data' => $logs]);
    }
}
