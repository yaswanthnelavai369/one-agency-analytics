<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\HealthScoreResource;
use App\Services\HealthScore\HealthScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientHealthScoreController extends Controller
{
    public function __construct(protected HealthScoreService $service) {}

    public function show(Request $request): JsonResponse
    {
        $client = $request->attributes->get('portal_client');
        $latest = $this->service->latest($client) ?? $this->service->computeAndStore($client);

        ['history' => $history, 'previous_overall' => $previousOverall] = $this->service->historyWithComparison($client, 90);

        return response()->json([
            'data' => new HealthScoreResource($latest),
            'previous_overall_score' => $previousOverall,
            'trend' => $history->map(fn ($row) => [
                'date' => $row->date->toDateString(),
                'overall_score' => $row->overall_score,
            ])->values(),
        ]);
    }
}
