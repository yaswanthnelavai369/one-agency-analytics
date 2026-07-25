<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Integrations\IntegrationManager;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use App\Services\Integration\IntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Spec explicitly gives the Client role "Connect Integrations" — clients can
 * link their own accounts (e.g. their own GA4 property) without needing an
 * agency team member to do it for them.
 */
class ClientIntegrationController extends Controller
{
    public function __construct(
        protected IntegrationManager $manager,
        protected IntegrationService $service,
        protected IntegrationRepositoryInterface $integrations,
    ) {}

    public function catalogue(): JsonResponse
    {
        return response()->json(['data' => $this->manager->catalogue()]);
    }

    public function index(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');

        return response()->json(['data' => $this->integrations->forClient($client->id)]);
    }

    public function connect(Request $request, string $provider): JsonResponse
    {
        abort_unless($this->manager->isSupported($provider), 404, 'Unknown integration provider.');

        $client = $request->attributes->get('portal_client');
        $redirectUri = route('integrations.callback', ['provider' => $provider]);
        $authUrl = $this->service->initiateConnect($provider, $client, $request->user(), $redirectUri);

        return response()->json(['auth_url' => $authUrl]);
    }

    public function disconnect(Request $request, int $integration): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $model = $this->integrations->findForClient($client->id, $integration);
        abort_if(! $model, 404);

        $this->service->disconnect($model);

        return response()->json(['message' => 'Integration disconnected.']);
    }
}
