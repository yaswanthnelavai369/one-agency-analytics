<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatThreadResource;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Spec: Agency role "Manage Chat" — any team member with support.* can read/reply on any client's thread. */
class ChatController extends Controller
{
    public function __construct(
        protected ChatService $service,
        protected ClientRepositoryInterface $clients,
    ) {}

    public function show(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $thread = $this->service->getOrCreateThread($clientModel);
        $this->service->markRead($thread, 'agency');

        return response()->json(['data' => new ChatThreadResource($thread->fresh('messages.sender'), 'agency')]);
    }

    public function sendMessage(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        $thread = $this->service->getOrCreateThread($clientModel);
        $this->service->sendMessage($thread, $request->user(), 'agency', $data['message']);

        return response()->json(['data' => new ChatThreadResource($thread->fresh('messages.sender'), 'agency')]);
    }
}
