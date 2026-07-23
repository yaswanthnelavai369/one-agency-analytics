<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\HealthScoreResource;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\HealthScore\HealthScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthScoreController extends Controller
{
    public function __construct(
        protected HealthScoreService $service,
        protected ClientRepositoryInterface $clients,
    ) {}

    public function show(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $latest = $this->service->latest($clientModel);

        if (! $latest) {
            // No score computed yet (e.g. brand-new client) — compute one on the fly
            // rather than showing an empty state.
            $latest = $this->service->computeAndStore($clientModel);
        }

        ['history' => $history, 'previous_overall' => $previousOverall] =
            $this->service->historyWithComparison($clientModel, 90);

        return response()->json([
            'data' => new HealthScoreResource($latest),
            'previous_overall_score' => $previousOverall,
            'trend' => $history->map(fn ($row) => [
                'date' => $row->date->toDateString(),
                'overall_score' => $row->overall_score,
            ])->values(),
        ]);
    }

    public function recalculate(Request $request, int $client): JsonResponse
    {
        $clientModel = $this->clients->findForAgency($request->user()->agency_id, $client);
        abort_if(! $clientModel, 404);

        $score = $this->service->computeAndStore($clientModel);

        return response()->json(['data' => new HealthScoreResource($score)]);
    }
}
