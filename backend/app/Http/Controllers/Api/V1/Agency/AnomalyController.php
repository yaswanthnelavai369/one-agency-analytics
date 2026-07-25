<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnomalyResource;
use App\Repositories\Contracts\AnomalyRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\Anomaly\AnomalyDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnomalyController extends Controller
{
    public function __construct(
        protected AnomalyDetectionService $service,
        protected AnomalyRepositoryInterface $anomalies,
        protected ClientRepositoryInterface $clients,
    ) {}

    public function index(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $anomalies = $this->service->list($clientModel, $request->query('status'));

        return response()->json(['data' => AnomalyResource::collection($anomalies)]);
    }

    /** Manually triggers detection now, instead of waiting for the nightly job. */
    public function detect(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $created = $this->service->run($clientModel);

        return response()->json([
            'message' => count($created) > 0
                ? count($created).' new anomaly/anomalies detected.'
                : 'No new anomalies detected.',
            'data' => AnomalyResource::collection($created),
        ]);
    }

    public function acknowledge(Request $request, int $client, int $anomaly): JsonResponse
    {
        $model = $this->anomalies->findForClient($client, $anomaly);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $model = $this->service->acknowledge($model, $request->user());

        return response()->json(['data' => new AnomalyResource($model)]);
    }

    public function resolve(Request $request, int $client, int $anomaly): JsonResponse
    {
        $model = $this->anomalies->findForClient($client, $anomaly);
        abort_if(! $model || $model->agency_id !== $request->user()->agency_id, 404);

        $model = $this->service->resolve($model);

        return response()->json(['data' => new AnomalyResource($model)]);
    }
}
