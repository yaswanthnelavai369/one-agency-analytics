<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\Client\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Scoped to the authenticated user's own agency via the
 * 'agency.owns' middleware — see routes/api.php.
 */
class ClientController extends Controller
{
    public function __construct(
        protected ClientService $clientService,
        protected ClientRepositoryInterface $clients,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $clients = $this->clientService->listForAgency(
            $request->user()->agency,
            $request->only(['search', 'status', 'sort'])
        );

        return response()->json(['data' => $clients]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'industry' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $client = $this->clientService->createForAgency($request->user()->agency, $data);

        return response()->json(['data' => $client], 201);
    }

    public function show(Request $request, int $client): JsonResponse
    {
        $client = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $client, 404);

        return response()->json(['data' => $client]);
    }

    public function update(Request $request, int $client): JsonResponse
    {
        $model = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $model, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'industry' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:active,onboarding,paused,archived'],
        ]);

        $model = $this->clients->update($model, $data);

        return response()->json(['data' => $model]);
    }

    public function destroy(Request $request, int $client): JsonResponse
    {
        $model = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $model, 404);

        $this->clients->delete($model);

        return response()->json(['message' => 'Client deleted.']);
    }
}
