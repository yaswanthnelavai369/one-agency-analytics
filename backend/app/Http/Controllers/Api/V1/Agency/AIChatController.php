<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\AI\QuickPrompts;
use App\Http\Controllers\Controller;
use App\Http\Resources\AIConversationResource;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\AI\AIChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct(
        protected AIChatService $service,
        protected ClientRepositoryInterface $clients,
    ) {}

    public function quickPrompts(): JsonResponse
    {
        return response()->json(['data' => QuickPrompts::all()]);
    }

    /** Fetches (or creates) this user's conversation for a client, with full message history. */
    public function show(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $conversation = $this->service->getOrCreateConversation($request->user(), $clientModel);

        return response()->json(['data' => new AIConversationResource($conversation)]);
    }

    public function sendMessage(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        $conversation = $this->service->getOrCreateConversation($request->user(), $clientModel);
        $this->service->sendMessage($conversation, $clientModel, $data['message']);

        return response()->json(['data' => new AIConversationResource($conversation->fresh('messages'))]);
    }
}
