<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Integrations\IntegrationManager;
use App\Jobs\SyncIntegrationDataJob;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use App\Services\Integration\IntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct(
        protected IntegrationManager $manager,
        protected IntegrationService $service,
        protected IntegrationRepositoryInterface $integrations,
        protected ClientRepositoryInterface $clients,
    ) {}

    /** Every provider the platform supports, for the "Add integration" picker. */
    public function catalogue(): JsonResponse
    {
        return response()->json(['data' => $this->manager->catalogue()]);
    }

    /** Connected + available integrations for one client. */
    public function index(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        return response()->json(['data' => $this->integrations->forClient($clientModel->id)]);
    }

    /** Step 1: redirect the browser to the provider's OAuth consent screen. */
    public function connect(Request $request, int $client, string $provider): JsonResponse
    {
        abort_unless($this->manager->isSupported($provider), 404, 'Unknown integration provider.');

        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $redirectUri = route('integrations.callback', ['provider' => $provider]);
        $authUrl = $this->service->initiateConnect($provider, $clientModel, $request->user(), $redirectUri);

        return response()->json(['auth_url' => $authUrl]);
    }

    /**
     * Step 2: OAuth provider redirects here. Not agency-scoped by middleware (the
     * request comes from Google, not our SPA) — the signed `state` param carries
     * and verifies the agency/client/user context instead.
     */
    public function callback(Request $request, string $provider): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['code' => ['required', 'string'], 'state' => ['required', 'string']]);

        $redirectUri = route('integrations.callback', ['provider' => $provider]);
        $frontendUrl = config('app.frontend_url').'/dashboard/integrations';

        try {
            $this->service->completeConnect($request->string('code'), $request->string('state'), $redirectUri);

            return redirect()->away("{$frontendUrl}?connected={$provider}");
        } catch (\Throwable $e) {
            return redirect()->away("{$frontendUrl}?error=".urlencode($e->getMessage()));
        }
    }

    public function syncNow(Request $request, int $client, int $integration): JsonResponse
    {
        $model = $this->integrations->findForClient($client, $integration);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        SyncIntegrationDataJob::dispatch($model->id);

        return response()->json(['message' => 'Sync started.']);
    }

    public function disconnect(Request $request, int $client, int $integration): JsonResponse
    {
        $model = $this->integrations->findForClient($client, $integration);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $this->service->disconnect($model);

        return response()->json(['message' => 'Integration disconnected.']);
    }
}
