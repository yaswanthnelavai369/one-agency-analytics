<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatThreadResource;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Spec: Client role "Chat With Agency". */
class ClientChatController extends Controller
{
    public function __construct(protected ChatService $service) {}

    public function show(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');

        $thread = $this->service->getOrCreateThread($client);
        $this->service->markRead($thread, 'client');

        return response()->json(['data' => new ChatThreadResource($thread->fresh('messages.sender'), 'client')]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        $thread = $this->service->getOrCreateThread($client);
        $this->service->sendMessage($thread, $request->user(), 'client', $data['message']);

        return response()->json(['data' => new ChatThreadResource($thread->fresh('messages.sender'), 'client')]);
    }
}
