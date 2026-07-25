<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\AI\QuickPrompts;
use App\Http\Controllers\Controller;
use App\Http\Resources\AIConversationResource;
use App\Services\AI\AIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Spec: Client role gets "View AI Suggestions" — same assistant agency users get, scoped to their own client. */
class ClientAIChatController extends Controller
{
    public function __construct(protected AIChatService $service) {}

    public function quickPrompts(): JsonResponse
    {
        return response()->json(['data' => QuickPrompts::all()]);
    }

    public function show(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $conversation = $this->service->getOrCreateConversation($request->user(), $client);

        return response()->json(['data' => new AIConversationResource($conversation)]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        $conversation = $this->service->getOrCreateConversation($request->user(), $client);
        $this->service->sendMessage($conversation, $client, $data['message']);

        return response()->json(['data' => new AIConversationResource($conversation->fresh('messages'))]);
    }
}
